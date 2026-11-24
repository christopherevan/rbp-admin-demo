<?php

use App\Support\QuotationTotals;

it('applies discount, then PPN on the discounted amount, then the down payment', function () {
    $totals = QuotationTotals::calculate(1_000_000, 10, 11, 200_000);

    expect($totals->toArray())->toBe([
        'subtotal' => 1_000_000,
        'discount' => 100_000,
        'taxBase' => 900_000,
        'ppn' => 99_000,
        'grandTotal' => 999_000,
        'downPayment' => 200_000,
        'balanceDue' => 799_000,
    ]);
});

it('supports quotations without PPN', function () {
    expect(QuotationTotals::calculate(500_000, 0, 0)->grandTotal)->toBe(500_000);
});

it('rounds to whole rupiah, half up', function () {
    $totals = QuotationTotals::calculate(333_333, 2.5, 11);

    expect($totals->discount)->toBe(8_333)
        ->and($totals->taxBase)->toBe(325_000)
        ->and($totals->ppn)->toBe(35_750)
        ->and($totals->grandTotal)->toBe(360_750);
});

it('never reports a negative balance', function () {
    expect(QuotationTotals::calculate(100_000, 0, 11, 500_000)->balanceDue)->toBe(0);
});
