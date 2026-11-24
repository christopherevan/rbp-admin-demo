<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceType;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Services\QuotationWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * One endpoint per workflow step on the quotation page. Each authorizes,
 * validates its input, and hands over to QuotationWorkflow.
 */
class QuotationActionController extends Controller
{
    /** File "uploads" are names only: the demo never stores a byte. */
    private const FILE_NAME = ['nullable', 'string', 'max:160', 'regex:/^[\w\-. ()]+$/u'];

    public function __construct(private QuotationWorkflow $workflow) {}

    public function recordPo(Request $request, Quotation $quotation): RedirectResponse
    {
        Gate::authorize('recordPo', $quotation);

        $data = $request->validate([
            'po_number' => ['required', 'string', 'max:60'],
            'po_file_name' => self::FILE_NAME,
        ]);

        $this->workflow->recordCustomerPo($quotation, $data['po_number'], $data['po_file_name'] ?? null, $request->user());

        return $this->done('Customer PO recorded. The order is now in progress.');
    }

    public function createSalesOrder(Request $request, Quotation $quotation): RedirectResponse
    {
        Gate::authorize('fulfil', $quotation);

        $data = $request->validate([
            'lines' => ['required', 'array', 'max:'.config('demo.max_quotation_lines')],
            'lines.*.id' => ['required', 'integer'],
            'lines.*.quantity' => ['required', 'integer', 'min:0'],
        ]);

        $selection = collect($data['lines'])->mapWithKeys(fn ($line) => [(int) $line['id'] => (int) $line['quantity']])->all();
        $salesOrder = $this->workflow->createSalesOrder($quotation, $selection, $request->user());

        return $this->done("Sales order {$salesOrder->number} created.");
    }

    public function deleteSalesOrder(Request $request, Quotation $quotation): RedirectResponse
    {
        Gate::authorize('fulfil', $quotation);

        $this->workflow->deleteSalesOrder($quotation, $request->user());

        return $this->done('Sales order deleted.');
    }

    public function createDeliveryOrder(Request $request, Quotation $quotation): RedirectResponse
    {
        Gate::authorize('fulfil', $quotation);

        $data = $request->validate([
            'ship_to_name' => ['required', 'string', 'max:120'],
            'ship_to_address' => ['required', 'string', 'max:320'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $deliveryOrder = $this->workflow->createDeliveryOrder($quotation, $data, $request->user());

        return $this->done("Delivery order {$deliveryOrder->number} created.");
    }

    public function completeDelivery(Request $request, Quotation $quotation): RedirectResponse
    {
        Gate::authorize('fulfil', $quotation);

        abort_unless($quotation->deliveryOrder, 404);

        $this->workflow->completeDelivery($quotation->deliveryOrder, $request->user());

        return $this->done('Delivery marked as received.');
    }

    public function createInvoice(Request $request, Quotation $quotation): RedirectResponse
    {
        Gate::authorize('bill', $quotation);

        $data = $request->validate([
            'type' => ['required', Rule::enum(InvoiceType::class)],
            'bill_to_name' => ['required', 'string', 'max:120'],
            'bill_to_address' => ['required', 'string', 'max:320'],
        ]);

        $billTo = ['bill_to_name' => $data['bill_to_name'], 'bill_to_address' => $data['bill_to_address']];

        $invoice = $data['type'] === InvoiceType::DownPayment->value
            ? $this->workflow->createDownPaymentInvoice($quotation, $billTo, $request->user())
            : $this->workflow->createFinalInvoice($quotation, $billTo, $request->user());

        return $this->done("{$invoice->type->label()} {$invoice->number} issued.");
    }

    public function recordPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        Gate::authorize('bill', $invoice->quotation);

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000000'],
            'note' => ['nullable', 'string', 'max:200'],
            'proof_file_name' => self::FILE_NAME,
        ]);

        $this->workflow->recordPayment($invoice, $data['amount'], $data['note'] ?? null, $data['proof_file_name'] ?? null, $request->user());

        return $this->done('Payment recorded.');
    }

    public function cancel(Request $request, Quotation $quotation): RedirectResponse
    {
        Gate::authorize('cancel', $quotation);

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $this->workflow->cancel($quotation, $data['reason'], $request->user());

        return $this->done('Quotation cancelled.');
    }

    private function done(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
