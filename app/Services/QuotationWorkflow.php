<?php

namespace App\Services;

use App\Enums\InvoiceType;
use App\Enums\QuotationStatus;
use App\Exceptions\WorkflowException;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use App\Support\DocumentNumber;
use App\Support\Money;
use App\Support\QuotationTotals;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * The quote-to-cash state machine.
 *
 *   NOT SUBMITTED ─lines─▶ WAITING CUSTOMER ─PO─▶ ON PROGRESS ─SO─▶ DO ─▶ invoice
 *                                                    │                       │
 *                                     (DP invoice)◀──┘     WAITING PAYMENT ◀─┘
 *                                                                 │ payments
 *                                                             COMPLETED
 *
 * Every transition is one method: it takes the acting user (never the
 * session, so the demo seeder can replay history through it), checks its
 * guard, runs in a transaction and writes to the audit log. Authorization is
 * separate (App\Policies\QuotationPolicy) and checked by the controllers.
 */
class QuotationWorkflow
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $by): Quotation
    {
        return DB::transaction(function () use ($data, $by) {
            $customer = Customer::findOrFail($data['customer_id']);

            $quotation = new Quotation($data);
            $quotation->sales_user_id = $data['sales_user_id'] ?? $by->id;
            $quotation->ppn_rate = $data['ppn_rate'] ?? config('demo.ppn_rate');
            $quotation->number = DocumentNumber::quotation($customer);
            $quotation->status = QuotationStatus::NotSubmitted;
            $quotation->save();

            $quotation->log('created', "Quotation created for {$customer->name}.", $by);

            return $quotation;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Quotation $quotation, array $data, User $by): void
    {
        $this->ensure($quotation->status->isOpenForEditing(), "Terms are locked once the customer's purchase order is in.");

        DB::transaction(function () use ($quotation, $data, $by) {
            $quotation->fill($data)->save();

            if ($quotation->wasChanged()) {
                $quotation->log('updated', 'Quotation terms updated.', $by, [
                    'fields' => array_values(array_diff(array_keys($quotation->getChanges()), ['updated_at'])),
                ]);
            }
        });
    }

    /**
     * Replace the line items. Saving them the first time sends the quotation
     * to the customer, unless $submit is false (an inquiry converted into a
     * draft for the sales rep to review).
     *
     * @param  list<array{part_id: int, quantity: int, unit_price: int}>  $lines
     */
    public function saveLines(Quotation $quotation, array $lines, User $by, bool $submit = true): void
    {
        $this->ensure($quotation->status->isOpenForEditing(), 'Line items are locked once the customer has sent a purchase order.');
        $this->ensure(count($lines) > 0, 'Add at least one line item.');
        $this->ensure(count($lines) <= config('demo.max_quotation_lines'), 'A quotation can have at most '.config('demo.max_quotation_lines').' lines.');

        $partIds = array_column($lines, 'part_id');
        $this->ensure(count($partIds) === count(array_unique($partIds)), 'Each part can only appear once. Combine the quantities instead.');

        DB::transaction(function () use ($quotation, $lines, $by, $submit) {
            $quotation->lines()->delete();

            foreach (array_values($lines) as $i => $line) {
                $quotation->lines()->create([
                    'part_id' => $line['part_id'],
                    'position' => $i + 1,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $line['quantity'] * $line['unit_price'],
                ]);
            }

            $quotation->unsetRelation('lines');
            $totals = QuotationTotals::forQuotation($quotation);

            $sent = $submit && $quotation->status === QuotationStatus::NotSubmitted;

            if ($sent) {
                $quotation->status = QuotationStatus::WaitingCustomer;
            }

            $quotation->touch();

            $quotation->log('lines_saved', sprintf(
                '%d line %s saved, grand total %s.%s',
                count($lines),
                count($lines) === 1 ? 'item' : 'items',
                Money::idr($totals->grandTotal),
                $sent ? ' Quotation sent to the customer.' : '',
            ), $by);
        });
    }

    public function recordCustomerPo(Quotation $quotation, string $poNumber, ?string $fileName, User $by): void
    {
        $this->ensure($quotation->status === QuotationStatus::WaitingCustomer, 'A purchase order can only be recorded while the quotation is waiting for the customer.');
        $this->ensure($quotation->lines()->exists(), 'The quotation has no line items.');

        DB::transaction(function () use ($quotation, $poNumber, $fileName, $by) {
            $quotation->customer_po_number = $poNumber;
            $quotation->customer_po_file = $fileName;
            $quotation->customer_po_at = now();
            $quotation->status = QuotationStatus::OnProgress;
            $quotation->save();

            $quotation->log('po_received', "Customer PO {$poNumber} received".($fileName ? " ({$fileName})" : '').'. Line items are now locked.', $by);
        });
    }

    /**
     * @param  array{bill_to_name: string, bill_to_address: string}  $billTo
     */
    public function createDownPaymentInvoice(Quotation $quotation, array $billTo, User $by): Invoice
    {
        $this->ensure($quotation->status === QuotationStatus::OnProgress, 'A down payment invoice needs a confirmed order (customer PO received).');
        $this->ensure($quotation->dp_amount > 0, 'This quotation has no down payment.');
        $this->ensure(! $quotation->downPaymentInvoice()->exists(), 'A down payment invoice has already been issued.');

        return DB::transaction(function () use ($quotation, $billTo, $by) {
            $invoice = $quotation->invoices()->create([
                ...$billTo,
                'type' => InvoiceType::DownPayment,
                'number' => DocumentNumber::shipping($quotation->customer),
                'amount_due' => $quotation->dp_amount,
                'status' => Invoice::PENDING,
                'created_by' => $by->id,
            ]);

            $quotation->log('invoice_created', "Down payment invoice {$invoice->number} issued for ".Money::idr($invoice->amount_due).'.', $by);

            return $invoice;
        });
    }

    /**
     * Turn the confirmed quotation into a sales order. The selection can be a
     * subset of the lines, or smaller quantities: customers often confirm
     * only part of what was quoted.
     *
     * @param  array<int, int>  $selection  quotation line id => quantity to order
     */
    public function createSalesOrder(Quotation $quotation, array $selection, User $by): SalesOrder
    {
        $this->ensure($quotation->status === QuotationStatus::OnProgress, 'A sales order needs a confirmed order (customer PO received).');
        $this->ensure(! $quotation->salesOrder()->exists(), 'This quotation already has a sales order.');

        $selection = array_filter($selection, fn ($quantity) => $quantity > 0);
        $this->ensure($selection !== [], 'Select at least one line for the sales order.');

        $lines = $quotation->lines()->get()->keyBy('id');

        foreach ($selection as $lineId => $quantity) {
            $line = $lines->get($lineId);
            $this->ensure($line !== null, 'The selection contains a line that is not on this quotation.');
            $this->ensure($quantity <= $line->quantity, "Line {$line->position}: can't order more than the {$line->quantity} quoted.");
        }

        return DB::transaction(function () use ($quotation, $selection, $lines, $by) {
            $salesOrder = $quotation->salesOrder()->create([
                'number' => DocumentNumber::salesOrder($quotation),
                'created_by' => $by->id,
            ]);

            foreach ($selection as $lineId => $quantity) {
                $line = $lines->get($lineId);
                $salesOrder->lines()->create([
                    'quotation_line_id' => $line->id,
                    'part_id' => $line->part_id,
                    'quantity' => $quantity,
                    'unit_price' => $line->unit_price,
                    'line_total' => $quantity * $line->unit_price,
                ]);
            }

            $partial = count($selection) < $lines->count()
                || collect($selection)->contains(fn ($quantity, $lineId) => $quantity < $lines->get($lineId)->quantity);

            $quotation->log('so_created', "Sales order {$salesOrder->number} created".($partial
                ? sprintf(' for part of the quotation (%d of %d lines).', count($selection), $lines->count())
                : '.'), $by);

            return $salesOrder;
        });
    }

    public function deleteSalesOrder(Quotation $quotation, User $by): void
    {
        $salesOrder = $quotation->salesOrder;

        $this->ensure($salesOrder !== null, 'There is no sales order to delete.');
        $this->ensure(! $quotation->deliveryOrder()->exists(), 'The sales order already has a delivery order.');

        DB::transaction(function () use ($quotation, $salesOrder, $by) {
            $salesOrder->delete();
            $quotation->unsetRelation('salesOrder');

            $quotation->log('so_deleted', "Sales order {$salesOrder->number} deleted.", $by);
        });
    }

    /**
     * @param  array{ship_to_name: string, ship_to_address: string, notes?: string|null}  $shipTo
     */
    public function createDeliveryOrder(Quotation $quotation, array $shipTo, User $by): DeliveryOrder
    {
        $this->ensure($quotation->status === QuotationStatus::OnProgress, 'Delivery orders can only be created for orders in progress.');
        $this->ensure($quotation->salesOrder()->exists(), 'Create a sales order first.');
        $this->ensure(! $quotation->deliveryOrder()->exists(), 'This quotation already has a delivery order.');

        return DB::transaction(function () use ($quotation, $shipTo, $by) {
            $deliveryOrder = $quotation->deliveryOrder()->create([
                ...$shipTo,
                'number' => DocumentNumber::shipping($quotation->customer),
                'status' => DeliveryOrder::ON_DELIVERY,
                'created_by' => $by->id,
            ]);

            // What ships leaves the warehouse: deduct it from stock. Services
            // (splicing, delivery charges) aren't stocked.
            $shipped = $quotation->salesOrder->lines;
            $stocked = Part::withTrashed()->whereIn('id', $shipped->pluck('part_id'))->where('stocked', true)->pluck('id');

            foreach ($shipped->whereIn('part_id', $stocked) as $line) {
                Part::withTrashed()->whereKey($line->part_id)->decrement('stock_qty', $line->quantity);
            }

            $shipped = $shipped->whereIn('part_id', $stocked);

            $quotation->log('do_created', "Delivery order {$deliveryOrder->number} created; stock deducted for {$shipped->count()} ".str('part')->plural($shipped->count()).'.', $by);

            return $deliveryOrder;
        });
    }

    public function completeDelivery(DeliveryOrder $deliveryOrder, User $by): void
    {
        $quotation = $deliveryOrder->quotation;

        $this->ensure(! $deliveryOrder->isDelivered(), 'This delivery has already been completed.');
        $this->ensure($quotation->status !== QuotationStatus::Cancelled, 'The quotation has been cancelled.');

        DB::transaction(function () use ($deliveryOrder, $quotation, $by) {
            $deliveryOrder->status = DeliveryOrder::COMPLETED;
            $deliveryOrder->delivered_at = now();
            $deliveryOrder->save();

            $quotation->log('delivered', "Delivery {$deliveryOrder->number} received by the customer.", $by);
        });
    }

    /**
     * Bill what was ordered (the sales order, not the quotation), less any
     * down payment that was invoiced. The amount is snapshotted on the
     * invoice so later changes can't move the goalposts for payment.
     *
     * @param  array{bill_to_name: string, bill_to_address: string}  $billTo
     */
    public function createFinalInvoice(Quotation $quotation, array $billTo, User $by): Invoice
    {
        $this->ensure($quotation->status === QuotationStatus::OnProgress, 'An invoice can only be issued for an order in progress.');
        $this->ensure($quotation->deliveryOrder()->exists(), 'Create a delivery order first.');
        $this->ensure(! $quotation->finalInvoice()->exists(), 'This quotation has already been invoiced.');

        return DB::transaction(function () use ($quotation, $billTo, $by) {
            $downPayment = $quotation->downPaymentInvoice()->exists() ? $quotation->dp_amount : 0;
            $totals = QuotationTotals::forSalesOrder($quotation, $downPayment);

            $invoice = $quotation->invoices()->create([
                ...$billTo,
                'type' => InvoiceType::Final,
                'delivery_order_id' => $quotation->deliveryOrder->id,
                'number' => $quotation->deliveryOrder->number,
                'amount_due' => $totals->balanceDue,
                'status' => Invoice::PENDING,
                'created_by' => $by->id,
            ]);

            $quotation->status = QuotationStatus::WaitingPayment;
            $quotation->save();

            $quotation->log('invoice_created', "Invoice {$invoice->number} issued for ".Money::idr($invoice->amount_due)
                .($downPayment ? ' (after the '.Money::idr($downPayment).' down payment)' : '')
                .'. Waiting for payment.', $by);

            if ($invoice->amount_due === 0) {
                $this->settle($invoice, $by);
            }

            return $invoice;
        });
    }

    public function recordPayment(Invoice $invoice, int $amount, ?string $note, ?string $proofFileName, User $by): Payment
    {
        $quotation = $invoice->quotation;
        $balance = $invoice->balance();

        $this->ensure($quotation->status !== QuotationStatus::Cancelled, 'The quotation has been cancelled.');
        $this->ensure(! $invoice->isPaid(), 'This invoice has already been paid in full.');
        $this->ensure($amount > 0, 'Enter the amount received.');
        $this->ensure($amount <= $balance, 'The payment is more than the '.Money::idr($balance).' outstanding.');

        return DB::transaction(function () use ($invoice, $quotation, $amount, $note, $proofFileName, $by, $balance) {
            $payment = $invoice->payments()->create([
                'amount' => $amount,
                'note' => $note,
                'proof_file_name' => $proofFileName,
                'received_by' => $by->id,
                'received_at' => now(),
            ]);

            $remaining = $balance - $amount;

            $quotation->log('payment_received', Money::idr($amount)." received for {$invoice->type->label()} {$invoice->number}"
                .($remaining > 0 ? ', '.Money::idr($remaining).' outstanding.' : ', paid in full.'), $by);

            if ($remaining === 0) {
                $this->settle($invoice, $by);
            }

            return $payment;
        });
    }

    public function cancel(Quotation $quotation, string $reason, User $by): void
    {
        $this->ensure(! $quotation->status->isClosed(), "This quotation is already {$quotation->status->label()}.");

        DB::transaction(function () use ($quotation, $reason, $by) {
            $quotation->status = QuotationStatus::Cancelled;
            $quotation->cancel_reason = $reason;
            $quotation->cancelled_at = now();
            $quotation->save();

            $quotation->log('cancelled', "Quotation cancelled: {$reason}", $by);
        });
    }

    /**
     * What can happen next, for the "Next step" card. Each action is valid
     * for the quotation's state; `allowed` and `reason` say whether this
     * user may take it. The UI renders this list rather than re-deriving the
     * state machine in TypeScript.
     *
     * @return list<array{key: string, label: string, primary: bool, allowed: bool, reason: string|null, invoice_id?: int}>
     */
    public function nextActions(Quotation $quotation, User $user): array
    {
        $quotation->loadMissing(['salesOrder', 'deliveryOrder', 'invoices.payments']);

        $gate = Gate::forUser($user);
        $actions = [];

        $add = function (string $key, string $label, string $ability, bool $primary = false, array $extra = []) use (&$actions, $gate, $quotation) {
            $response = $gate->inspect($ability, $quotation);
            $actions[] = [
                'key' => $key,
                'label' => $label,
                'primary' => $primary,
                'allowed' => $response->allowed(),
                'reason' => $response->allowed() ? null : $response->message(),
                ...$extra,
            ];
        };

        $downPayment = $quotation->invoices->firstWhere('type', InvoiceType::DownPayment);
        $final = $quotation->invoices->firstWhere('type', InvoiceType::Final);
        $delivery = $quotation->deliveryOrder;

        switch ($quotation->status) {
            case QuotationStatus::NotSubmitted:
                $add('edit_lines', 'Add line items & send', 'update', true);
                break;

            case QuotationStatus::WaitingCustomer:
                $add('record_po', 'Record customer PO', 'recordPo', true);
                $add('edit_lines', 'Revise line items', 'update');
                break;

            case QuotationStatus::OnProgress:
                if (! $quotation->salesOrder) {
                    $add('create_so', 'Create sales order', 'fulfil', true);
                } elseif (! $delivery) {
                    $add('create_do', 'Create delivery order', 'fulfil', true);
                    $add('delete_so', 'Delete sales order', 'fulfil');
                } else {
                    $add('create_invoice', 'Issue invoice', 'bill', true);

                    if (! $delivery->isDelivered()) {
                        $add('complete_delivery', 'Mark as delivered', 'fulfil');
                    }
                }

                if ($quotation->dp_amount > 0 && ! $downPayment) {
                    $add('create_dp_invoice', 'Issue down payment invoice', 'bill');
                }
                break;

            case QuotationStatus::WaitingPayment:
                if ($final && ! $final->isPaid()) {
                    $add('record_payment', 'Record payment', 'bill', true, ['invoice_id' => $final->id]);
                }

                if ($delivery && ! $delivery->isDelivered()) {
                    $add('complete_delivery', 'Mark as delivered', 'fulfil');
                }
                break;

            default:
                break;
        }

        if ($downPayment && ! $downPayment->isPaid() && ! $quotation->status->isClosed()) {
            $add('record_payment', 'Record down payment', 'bill', false, ['invoice_id' => $downPayment->id]);
        }

        if (! $quotation->status->isClosed()) {
            $add('cancel', 'Cancel quotation', 'cancel');
        }

        return $actions;
    }

    private function settle(Invoice $invoice, User $by): void
    {
        $invoice->status = Invoice::PAID;
        $invoice->paid_at = now();
        $invoice->save();

        if ($invoice->type === InvoiceType::Final) {
            $quotation = $invoice->quotation;
            $quotation->status = QuotationStatus::Completed;
            $quotation->completed_at = now();
            $quotation->save();

            $quotation->log('completed', 'Invoice paid in full. Quotation completed.', $by);
        }
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw new WorkflowException($message);
        }
    }
}
