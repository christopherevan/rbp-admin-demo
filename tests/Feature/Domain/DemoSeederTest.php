<?php

use App\Enums\InquiryStatus;
use App\Enums\InvoiceType;
use App\Enums\QuotationStatus;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\Part;
use App\Models\Quotation;
use App\Models\QuotationLine;
use Database\Seeders\DemoSeeder;

beforeEach(fn () => $this->seed(DemoSeeder::class));

it('covers every quotation and inquiry status', function () {
    expect(Quotation::distinct()->pluck('status')->map->value->sort()->values()->all())
        ->toEqualCanonicalizing(array_column(QuotationStatus::cases(), 'value'))
        ->and(Inquiry::distinct()->pluck('status')->map->value->all())
        ->toEqualCanonicalizing(array_column(InquiryStatus::cases(), 'value'));
});

it('settles every completed quotation exactly', function () {
    Quotation::where('status', QuotationStatus::Completed)->with('invoices.payments')->get()->each(function (Quotation $quotation) {
        $final = $quotation->invoices->firstWhere('type', InvoiceType::Final);

        expect($final->isPaid())->toBeTrue()
            ->and($final->payments->sum('amount'))->toBe($final->amount_due);
    });
});

it('keeps all history in the past and stock non-negative', function () {
    expect(ActivityLog::where('created_at', '>', now())->exists())->toBeFalse()
        ->and(Part::withTrashed()->where('stock_qty', '<', 0)->exists())->toBeFalse();
});

it('archives the closed customer and its discontinued part', function () {
    expect(Customer::onlyTrashed()->pluck('code')->all())->toBe(['AMS'])
        ->and(Part::onlyTrashed()->pluck('part_number')->all())->toBe(['BT-300FT-PP-0254'])
        ->and(QuotationLine::whereHas('part', fn ($q) => $q->onlyTrashed())->exists())->toBeTrue();
});

it('turns the quoted inquiry into a quotation', function () {
    $inquiry = Inquiry::where('status', InquiryStatus::Quoted)->firstOrFail();

    expect($inquiry->quotation)->not->toBeNull()
        ->and($inquiry->quotation->lines()->count())->toBeGreaterThan(0);
});
