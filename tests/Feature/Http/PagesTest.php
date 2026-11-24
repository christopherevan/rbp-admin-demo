<?php

use App\Enums\InvoiceType;
use App\Enums\QuotationStatus;
use App\Models\CatalogProduct;
use App\Models\CatalogProductType;
use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\Quotation;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(DemoSeeder::class));

it('renders every page for both roles', function (string $username) {
    $this->actingAs(User::where('username', $username)->firstOrFail());

    $completed = Quotation::where('status', QuotationStatus::Completed)->whereHas('invoices', fn ($q) => $q->where('type', InvoiceType::DownPayment))->firstOrFail();
    $pages = [
        '/dashboard' => 'dashboard',
        '/dashboard?part=1' => 'dashboard',
        '/quotations' => 'quotations/index',
        '/quotations?status=on_progress&sort=-number&q=2026' => 'quotations/index',
        '/quotations/create' => 'quotations/form',
        '/quotations/create?customer='.Customer::first()->ulid => 'quotations/form',
        '/inquiries?status=new' => 'inquiries/index',
        '/inquiries/'.Inquiry::first()->ulid => 'inquiries/show',
        '/customers' => 'customers/index',
        '/customers?archived=1' => 'customers/index',
        '/customers/create' => 'customers/form',
        '/customers/'.Customer::first()->ulid.'/edit' => 'customers/form',
        '/parts?stock=low' => 'parts/index',
        '/parts?discontinued=1' => 'parts/index',
        '/uoms' => 'uoms/index',
        '/catalog/descriptions' => 'catalog/descriptions',
        '/catalog/descriptions?status=approved&selected='.CatalogProduct::first()->ulid => 'catalog/descriptions',
        '/catalog/types' => 'catalog/types/index',
        '/catalog/types/'.CatalogProductType::firstOrFail()->ulid => 'catalog/types/show',
    ];

    foreach (Quotation::all() as $quotation) {
        $pages['/quotations/'.$quotation->ulid] = 'quotations/show';
    }

    foreach (['quotation', 'delivery-order', 'invoice', 'down-payment'] as $document) {
        $pages["/quotations/{$completed->ulid}/print/{$document}"] = 'print/document';
    }

    foreach ($pages as $uri => $component) {
        $this->get($uri)->assertOk()->assertInertia(fn (Assert $page) => $page->component($component));
    }
})->with(['capybara', 'narwhal']);

it('serves the public pages without a session', function () {
    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->component('welcome'));
    $this->get('/about')->assertOk()->assertInertia(fn (Assert $page) => $page->component('about'));
    $this->get('/request-quote')->assertOk()->assertInertia(fn (Assert $page) => $page->component('public/request-quote'));
});

it('loads the price history chart on demand', function () {
    $this->actingAs(User::where('username', 'capybara')->first());

    $version = $this->get('/dashboard')->viewData('page')['version'];

    $this->get('/dashboard?part=1', ['X-Inertia' => 'true', 'X-Inertia-Partial-Component' => 'dashboard', 'X-Inertia-Partial-Data' => 'priceHistory', 'X-Inertia-Version' => (string) $version])
        ->assertOk()
        ->assertJsonPath('props.priceHistory.part.id', 1);
});
