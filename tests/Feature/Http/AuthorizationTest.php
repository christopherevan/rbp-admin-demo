<?php

use App\Enums\DescriptionStatus;
use App\Models\CatalogProduct;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->owner = salesRep();
    $this->otherRep = salesRep();
    $this->admin = admin();
    $this->quotation = confirmed($this->owner, [[part(), 2, 100_000]]);
});

it('keeps fulfilment and billing admin-only, with the reason as a toast', function () {
    $this->actingAs($this->owner)
        ->from(route('quotations.show', $this->quotation))
        ->post(route('quotations.sales-order.store', $this->quotation), [
            'lines' => [['id' => $this->quotation->lines()->value('id'), 'quantity' => 2]],
        ])
        ->assertRedirect(route('quotations.show', $this->quotation));

    expect($this->quotation->salesOrder()->exists())->toBeFalse()
        ->and(session('inertia.flash_data.toast.message') ?? collect(session()->all())->flatten()->implode(' '))->toContain('Admin only');

    $this->actingAs($this->admin)
        ->post(route('quotations.sales-order.store', $this->quotation), [
            'lines' => [['id' => $this->quotation->lines()->value('id'), 'quantity' => 2]],
        ]);

    expect($this->quotation->salesOrder()->exists())->toBeTrue();
});

it('lets only the owning rep or an admin cancel', function () {
    $this->actingAs($this->otherRep)->post(route('quotations.cancel', $this->quotation), ['reason' => 'Not mine']);
    expect($this->quotation->fresh()->status->value)->toBe('on_progress');

    $this->actingAs($this->owner)->post(route('quotations.cancel', $this->quotation), ['reason' => 'Customer changed plans']);
    expect($this->quotation->fresh()->status->value)->toBe('cancelled');
});

it('explains on the page why a step is unavailable', function () {
    $this->actingAs($this->owner)
        ->get(route('quotations.show', $this->quotation))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextActions.0.key', 'create_so')
            ->where('nextActions.0.allowed', false)
            ->where('can.fulfil', false)
            ->etc());
});

it('reserves AI description decisions for admins', function () {
    $product = CatalogProduct::create([
        'supplier' => 'Beltora', 'title' => 'Test Belt', 'category' => 'Belts', 'family' => 'belt',
        'facts' => [], 'manufacturer_text' => 'x', 'description_en' => 'x', 'description_id' => 'x', 'meta_en' => 'x',
        'ai_status' => DescriptionStatus::Pending, 'ai_findings' => ['errors' => [], 'warnings' => []], 'ai_meta' => [],
    ]);

    $this->actingAs($this->owner)->post(route('catalog.descriptions.decide', $product), ['decision' => 'approve']);
    expect($product->fresh()->ai_status)->toBe(DescriptionStatus::Pending);

    $this->actingAs($this->admin)->post(route('catalog.descriptions.decide', $product), ['decision' => 'approve']);
    expect($product->fresh()->ai_status)->toBe(DescriptionStatus::Approved);
});

it('blocks approving a description the guard rejected', function () {
    $product = CatalogProduct::create([
        'supplier' => 'Beltora', 'title' => 'Test Belt', 'category' => 'Belts', 'family' => 'belt',
        'facts' => [], 'manufacturer_text' => 'x', 'description_en' => 'x', 'description_id' => 'x', 'meta_en' => 'x',
        'ai_status' => DescriptionStatus::Pending, 'ai_findings' => ['errors' => ['Unverified figure'], 'warnings' => []], 'ai_meta' => [],
    ]);

    $this->actingAs($this->admin)->post(route('catalog.descriptions.decide', $product), ['decision' => 'approve'])
        ->assertSessionHasErrors('workflow');

    expect($product->fresh()->ai_status)->toBe(DescriptionStatus::Pending);
});
