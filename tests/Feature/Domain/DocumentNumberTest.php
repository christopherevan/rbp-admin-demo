<?php

use App\Support\DocumentNumber;
use Carbon\CarbonImmutable;

it('numbers quotations per customer code with a daily counter', function () {
    $customer = customer('SPL');
    $day = CarbonImmutable::parse('2026-10-27 10:00');

    expect(DocumentNumber::quotation($customer, $day))->toBe('2026/SPL/2710-001')
        ->and(DocumentNumber::quotation($customer, $day))->toBe('2026/SPL/2710-002')
        ->and(DocumentNumber::quotation($customer, $day->addDay()))->toBe('2026/SPL/2810-001');
});

it('shares one counter between delivery orders and down payment invoices', function () {
    $customer = customer('TKM');
    $day = CarbonImmutable::parse('2026-12-03 09:00');

    expect(DocumentNumber::shipping($customer, $day))->toBe('2026/XII/TKM/0312-001')
        ->and(DocumentNumber::shipping($customer, $day))->toBe('2026/XII/TKM/0312-002');
});

it('numbers inquiries by month and day', function () {
    expect(DocumentNumber::inquiry(CarbonImmutable::parse('2026-01-05')))->toBe('INQ/2026/0105-001');
});

it('converts months to roman numerals', function () {
    expect(array_map(DocumentNumber::roman(...), [1, 4, 9, 12]))->toBe(['I', 'IV', 'IX', 'XII']);
});
