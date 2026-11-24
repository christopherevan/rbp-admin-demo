<?php

use App\Enums\InvoiceType;
use App\Enums\QuotationStatus;
use App\Exceptions\WorkflowException;
use App\Services\QuotationWorkflow;
use App\Support\QuotationTotals;

beforeEach(function () {
    $this->workflow = app(QuotationWorkflow::class);
    $this->rep = salesRep();
    $this->admin = admin();
});

it('takes a quotation all the way from draft to completed', function () {
    $belt = part('BELT', 1_000_000, stock: 100);
    $service = part('SVC', 500_000, stock: 0, stocked: false);

    $quotation = quotation($this->rep, terms: ['discount_pct' => 10, 'dp_amount' => 1_000_000]);
    expect($quotation->status)->toBe(QuotationStatus::NotSubmitted);

    $this->workflow->saveLines($quotation, [
        ['part_id' => $belt->id, 'quantity' => 10, 'unit_price' => 1_000_000],
        ['part_id' => $service->id, 'quantity' => 1, 'unit_price' => 500_000],
    ], $this->rep);
    expect($quotation->fresh()->status)->toBe(QuotationStatus::WaitingCustomer);

    $this->workflow->recordCustomerPo($quotation, 'PO-77', 'po-77.pdf', $this->rep);
    expect($quotation->fresh()->status)->toBe(QuotationStatus::OnProgress);

    $dp = $this->workflow->createDownPaymentInvoice($quotation, ['bill_to_name' => 'X', 'bill_to_address' => 'Y'], $this->admin);
    $this->workflow->recordPayment($dp, 1_000_000, null, null, $this->admin);
    expect($dp->fresh()->isPaid())->toBeTrue();

    $lines = $quotation->lines()->pluck('quantity', 'id')->all();
    $this->workflow->createSalesOrder($quotation, $lines, $this->admin);
    $delivery = $this->workflow->createDeliveryOrder($quotation->fresh(), ['ship_to_name' => 'X', 'ship_to_address' => 'Y'], $this->admin);

    expect($belt->fresh()->stock_qty)->toBe(90)
        ->and($service->fresh()->stock_qty)->toBe(0);

    $this->workflow->completeDelivery($delivery, $this->admin);
    $invoice = $this->workflow->createFinalInvoice($quotation->fresh(), ['bill_to_name' => 'X', 'bill_to_address' => 'Y'], $this->admin);

    // 10.5M less 10% = 9.45M, plus 11% PPN = 10,489,500, less the 1M down payment.
    expect($invoice->number)->toBe($delivery->number)
        ->and($invoice->amount_due)->toBe(9_489_500)
        ->and($quotation->fresh()->status)->toBe(QuotationStatus::WaitingPayment);

    $this->workflow->recordPayment($invoice, 4_000_000, 'first', null, $this->admin);
    expect($quotation->fresh()->status)->toBe(QuotationStatus::WaitingPayment);

    $this->workflow->recordPayment($invoice->fresh(), 5_489_500, 'rest', null, $this->admin);

    $quotation->refresh();
    expect($quotation->status)->toBe(QuotationStatus::Completed)
        ->and($quotation->completed_at)->not->toBeNull()
        ->and($quotation->activities()->pluck('action')->all())->toContain(
            'created', 'lines_saved', 'po_received', 'invoice_created', 'payment_received', 'so_created', 'do_created', 'delivered', 'completed',
        );
});

it('bills only what a partial sales order contains', function () {
    $a = part('A', 1_000_000);
    $b = part('B', 2_000_000);
    $quotation = confirmed($this->rep, [[$a, 10, 1_000_000], [$b, 5, 2_000_000]], ['dp_amount' => 500_000]);

    $this->workflow->createDownPaymentInvoice($quotation, ['bill_to_name' => 'X', 'bill_to_address' => 'Y'], $this->admin);

    $lineA = $quotation->lines()->where('part_id', $a->id)->first();
    $this->workflow->createSalesOrder($quotation, [$lineA->id => 4], $this->admin);
    $this->workflow->createDeliveryOrder($quotation->fresh(), ['ship_to_name' => 'X', 'ship_to_address' => 'Y'], $this->admin);
    $invoice = $this->workflow->createFinalInvoice($quotation->fresh(), ['bill_to_name' => 'X', 'bill_to_address' => 'Y'], $this->admin);

    $expected = QuotationTotals::calculate(4_000_000, 0, 11, 500_000)->balanceDue;

    expect($invoice->amount_due)->toBe($expected)->toBe(3_940_000);
});

it('guards every step', function (Closure $step, string $message) {
    expect(fn () => $step->call($this))->toThrow(WorkflowException::class, $message);
})->with([
    'PO before any lines' => [function () {
        $this->workflow->recordCustomerPo(quotation($this->rep), 'PO', null, $this->rep);
    }, 'waiting for the customer'],
    'editing lines after the PO' => [function () {
        $p = part();
        $this->workflow->saveLines(confirmed($this->rep, [[$p, 1, 1000]]), [['part_id' => $p->id, 'quantity' => 2, 'unit_price' => 1000]], $this->rep);
    }, 'locked'],
    'duplicate parts' => [function () {
        $p = part();
        $this->workflow->saveLines(quotation($this->rep), [['part_id' => $p->id, 'quantity' => 1, 'unit_price' => 1], ['part_id' => $p->id, 'quantity' => 1, 'unit_price' => 1]], $this->rep);
    }, 'only appear once'],
    'a second sales order' => [function () {
        $q = confirmed($this->rep, [[part(), 1, 1000]]);
        $lines = $q->lines()->pluck('quantity', 'id')->all();
        $this->workflow->createSalesOrder($q, $lines, $this->admin);
        $this->workflow->createSalesOrder($q, $lines, $this->admin);
    }, 'already has a sales order'],
    'ordering more than quoted' => [function () {
        $q = confirmed($this->rep, [[part(), 3, 1000]]);
        $this->workflow->createSalesOrder($q, [$q->lines()->value('id') => 4], $this->admin);
    }, "can't order more"],
    'a delivery order without a sales order' => [function () {
        $this->workflow->createDeliveryOrder(confirmed($this->rep, [[part(), 1, 1000]]), ['ship_to_name' => 'X', 'ship_to_address' => 'Y'], $this->admin);
    }, 'sales order first'],
    'a down payment invoice without a down payment' => [function () {
        $this->workflow->createDownPaymentInvoice(confirmed($this->rep, [[part(), 1, 1000]]), ['bill_to_name' => 'X', 'bill_to_address' => 'Y'], $this->admin);
    }, 'no down payment'],
    'overpaying' => [function () {
        $q = confirmed($this->rep, [[part(), 1, 1_000_000]], ['dp_amount' => 300_000]);
        $dp = $this->workflow->createDownPaymentInvoice($q, ['bill_to_name' => 'X', 'bill_to_address' => 'Y'], $this->admin);
        $this->workflow->recordPayment($dp, 300_001, null, null, $this->admin);
    }, 'more than'],
    'cancelling twice' => [function () {
        $q = quotation($this->rep);
        $this->workflow->cancel($q, 'No longer needed', $this->rep);
        $this->workflow->cancel($q->fresh(), 'Again', $this->rep);
    }, 'already Cancelled'],
]);

it('offers the next steps the current state allows, with permissions', function () {
    $quotation = confirmed($this->rep, [[part(), 1, 1000]]);

    $forSales = collect($this->workflow->nextActions($quotation, $this->rep))->keyBy('key');
    $forAdmin = collect($this->workflow->nextActions($quotation, $this->admin))->keyBy('key');

    expect($forSales['create_so'])->toMatchArray(['primary' => true, 'allowed' => false])
        ->and($forSales['create_so']['reason'])->toContain('Admin only')
        ->and($forSales['cancel']['allowed'])->toBeTrue()
        ->and($forAdmin['create_so']['allowed'])->toBeTrue()
        ->and($forAdmin->has('create_invoice'))->toBeFalse();
});

it('records the final invoice type and the down payment type', function () {
    expect(InvoiceType::Final->label())->toBe('Invoice')
        ->and(InvoiceType::DownPayment->label())->toBe('Down payment invoice');
});
