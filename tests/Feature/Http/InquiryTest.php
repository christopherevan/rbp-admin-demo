<?php

use App\Enums\InquiryStatus;
use App\Enums\QuotationStatus;
use App\Models\Inquiry;

beforeEach(function () {
    $this->admin = admin();
    $this->rep = salesRep();
    $this->part = part('BT-1', 250_000);
});

function submitInquiry(array $overrides = []): Inquiry
{
    test()->post(route('quote-request.store'), [
        'name' => 'Dewi Buyer',
        'company' => 'PT Pembeli',
        'email' => 'dewi@pembeli.example',
        'items' => [
            ['part_id' => test()->part->id, 'quantity' => 12],
            ['title' => 'Custom guide rail', 'quantity' => 4],
        ],
        ...$overrides,
    ])->assertRedirect(route('quote-request.create'));

    return Inquiry::latest('id')->firstOrFail();
}

it('turns the public form into a numbered NEW inquiry', function () {
    $inquiry = submitInquiry();

    expect($inquiry->status)->toBe(InquiryStatus::New)
        ->and($inquiry->number)->toStartWith('INQ/'.now()->format('Y/md'))
        ->and($inquiry->items)->toHaveCount(2)
        ->and($inquiry->items[0]->part_number)->toBe('BT-1');
});

it('rejects bots that fill the honeypot', function () {
    $this->post(route('quote-request.store'), ['name' => 'x', 'email' => 'x@x.example', 'items' => [['title' => 'x', 'quantity' => 1]], 'website' => 'spam'])
        ->assertSessionHasErrors('website');
});

it('triages an inquiry into a draft quotation', function () {
    $inquiry = submitInquiry();
    $customer = customer('PBL');

    $this->actingAs($this->admin)->post(route('inquiries.assign', $inquiry), ['user_id' => $this->rep->id]);
    expect($inquiry->fresh()->status)->toBe(InquiryStatus::InReview);

    $this->actingAs($this->rep)->post(route('inquiries.link-customer', $inquiry), ['customer_id' => $customer->id]);
    $this->actingAs($this->admin)->post(route('inquiries.approve', $inquiry));
    expect($inquiry->fresh()->status)->toBe(InquiryStatus::Approved);

    $this->actingAs($this->rep)->post(route('inquiries.convert', $inquiry));

    $inquiry->refresh();
    expect($inquiry->status)->toBe(InquiryStatus::Quoted)
        ->and($inquiry->quotation->status)->toBe(QuotationStatus::NotSubmitted)
        ->and($inquiry->quotation->sales_user_id)->toBe($this->rep->id)
        ->and($inquiry->quotation->lines)->toHaveCount(1)
        ->and($inquiry->quotation->lines[0]->unit_price)->toBe(250_000);
});

it('needs a reason to reject, and admin rights to triage', function () {
    $inquiry = submitInquiry();

    $this->actingAs($this->admin)->post(route('inquiries.reject', $inquiry), ['reason' => ''])->assertSessionHasErrors('reason');

    $this->actingAs($this->rep)->post(route('inquiries.reject', $inquiry), ['reason' => 'Spam']);
    expect($inquiry->fresh()->status)->toBe(InquiryStatus::New);

    $this->actingAs($this->admin)->post(route('inquiries.reject', $inquiry), ['reason' => 'Spam']);
    expect($inquiry->fresh()->status)->toBe(InquiryStatus::Rejected);
});
