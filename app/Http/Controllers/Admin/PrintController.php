<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceType;
use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Support\QuotationTotals;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Printable quotation, delivery order and invoices, laid out for A4 and
 * printed (or saved as PDF) from the browser. Production renders these with
 * dompdf and keeps every print as a versioned, hashed file.
 */
class PrintController extends Controller
{
    public function show(Quotation $quotation, string $document): Response
    {
        $quotation->load(['customer', 'contact', 'salesRep:id,name', 'lines.part.uom', 'salesOrder.lines.part.uom', 'deliveryOrder', 'invoices']);

        $invoice = match ($document) {
            'invoice' => $quotation->invoices->firstWhere('type', InvoiceType::Final),
            'down-payment' => $quotation->invoices->firstWhere('type', InvoiceType::DownPayment),
            default => null,
        };

        abort_if(in_array($document, ['invoice', 'down-payment'], true) && ! $invoice, 404);
        abort_if($document === 'delivery-order' && ! $quotation->deliveryOrder, 404);
        abort_if($document === 'quotation' && $quotation->lines->isEmpty(), 404);

        // The quotation lists what was quoted; everything after it lists
        // what was ordered.
        $lines = $document === 'quotation' || ! $quotation->salesOrder
            ? $quotation->lines
            : $quotation->salesOrder->lines;

        $downPayment = $quotation->invoices->firstWhere('type', InvoiceType::DownPayment) ? $quotation->dp_amount : 0;

        $totals = match ($document) {
            'quotation' => QuotationTotals::forQuotation($quotation),
            'invoice' => QuotationTotals::forSalesOrder($quotation, $downPayment),
            default => null,
        };

        $number = match ($document) {
            'quotation' => $quotation->number,
            'delivery-order' => $quotation->deliveryOrder->number,
            default => $invoice->number,
        };

        return Inertia::render('print/document', [
            'document' => $document,
            'number' => $number,
            'date' => match ($document) {
                'quotation' => $quotation->created_at,
                'delivery-order' => $quotation->deliveryOrder->created_at,
                default => $invoice->created_at,
            },
            'company' => config('demo.company'),
            'quotation' => [
                'id' => $quotation->id,
                'ulid' => $quotation->ulid,
                'number' => $quotation->number,
                'customer_po_number' => $quotation->customer_po_number,
                'delivery_point' => $quotation->delivery_point,
                'lead_time' => $quotation->lead_time_value.' '.$quotation->lead_time_unit,
                'validity' => $quotation->validity_value.' '.$quotation->validity_unit,
                'payment' => $quotation->payment_method === 'cod' ? 'Cash on delivery' : "Invoice, {$quotation->payment_terms_days} days",
                'notes' => $quotation->notes,
                'sales_rep' => $quotation->salesRep->name,
                'discount_pct' => (float) $quotation->discount_pct,
                'ppn_rate' => $quotation->ppn_rate,
            ],
            'recipient' => match ($document) {
                'delivery-order' => ['name' => $quotation->deliveryOrder->ship_to_name, 'address' => $quotation->deliveryOrder->ship_to_address],
                'invoice', 'down-payment' => ['name' => $invoice->bill_to_name, 'address' => $invoice->bill_to_address],
                default => ['name' => $quotation->customer->name, 'address' => $quotation->customer->address()],
            },
            'attention' => $quotation->contact?->displayName(),
            'lines' => $document === 'down-payment' ? [] : $lines->values()->map(fn ($line, $i) => [
                'no' => $i + 1,
                'part_number' => $line->part->part_number,
                'description' => $line->part->description,
                'uom' => $line->part->uom->code,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'line_total' => $line->line_total,
            ]),
            'totals' => $totals?->toArray(),
            'amountDue' => $invoice?->amount_due,
            'deliveryNotes' => $quotation->deliveryOrder?->notes,
        ]);
    }
}
