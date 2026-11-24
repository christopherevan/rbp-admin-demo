<?php

namespace App\Support;

use App\Models\Quotation;

/**
 * Subtotal → discount → PPN → down payment, in whole rupiah.
 *
 * PPN is charged on the discounted amount (the Indonesian tax base). The
 * original system taxed the pre-discount subtotal; switching back is a
 * one-line change to $taxBase below.
 */
final readonly class QuotationTotals
{
    public function __construct(
        public int $subtotal,
        public int $discount,
        public int $taxBase,
        public int $ppn,
        public int $grandTotal,
        public int $downPayment,
        public int $balanceDue,
    ) {}

    public static function calculate(int $subtotal, float|string $discountPct, int $ppnRate, int $downPayment = 0): self
    {
        $discount = (int) round($subtotal * (float) $discountPct / 100);
        $taxBase = $subtotal - $discount;
        $ppn = (int) round($taxBase * $ppnRate / 100);
        $grandTotal = $taxBase + $ppn;

        return new self(
            subtotal: $subtotal,
            discount: $discount,
            taxBase: $taxBase,
            ppn: $ppn,
            grandTotal: $grandTotal,
            downPayment: $downPayment,
            balanceDue: max(0, $grandTotal - $downPayment),
        );
    }

    /** What the customer was quoted. */
    public static function forQuotation(Quotation $quotation): self
    {
        $subtotal = (int) ($quotation->relationLoaded('lines')
            ? $quotation->lines->sum('line_total')
            : $quotation->lines()->sum('line_total'));

        return self::calculate($subtotal, $quotation->discount_pct, $quotation->ppn_rate, $quotation->dp_amount);
    }

    /**
     * What the customer ordered, which is what gets invoiced: a partial sales
     * order only bills the lines (and quantities) it contains.
     */
    public static function forSalesOrder(Quotation $quotation, int $downPayment = 0): ?self
    {
        $salesOrder = $quotation->salesOrder;

        if (! $salesOrder) {
            return null;
        }

        return self::calculate(
            (int) $salesOrder->lines()->sum('line_total'),
            $quotation->discount_pct,
            $quotation->ppn_rate,
            $downPayment,
        );
    }

    /** @return array<string, int> */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'taxBase' => $this->taxBase,
            'ppn' => $this->ppn,
            'grandTotal' => $this->grandTotal,
            'downPayment' => $this->downPayment,
            'balanceDue' => $this->balanceDue,
        ];
    }
}
