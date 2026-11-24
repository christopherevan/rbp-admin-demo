<?php

use App\Models\CatalogProduct;
use App\Models\CatalogProductType;
use App\Models\CatalogProductTypeKeyAttribute;
use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Quotation;
use App\Models\Uom;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

/** Milliseconds since the epoch encoded in a ULID's first ten characters. */
function ulidMilliseconds(string $ulid): int
{
    $ms = 0;

    foreach (str_split(strtoupper(substr($ulid, 0, 10))) as $char) {
        $ms = $ms * 32 + strpos('0123456789ABCDEFGHJKMNPQRSTVWXYZ', $char);
    }

    return $ms;
}

it('routes quotations by ULID, never by the integer id', function () {
    $admin = admin();
    $quotation = quotation($admin, [[part(), 1, 1000]]);

    expect(Str::isUlid($quotation->ulid))->toBeTrue()
        ->and($quotation->ulid)->toBe(strtolower($quotation->ulid))
        ->and(route('quotations.show', $quotation))->toEndWith('/quotations/'.$quotation->ulid);

    $this->actingAs($admin)->get('/quotations/'.$quotation->ulid)->assertOk();
    $this->actingAs($admin)->get('/quotations/'.$quotation->id)->assertNotFound();
    $this->actingAs($admin)->get('/quotations/'.$quotation->id.'/print/quotation')->assertNotFound();
    $this->actingAs($admin)->post('/quotations/'.$quotation->id.'/cancel', ['reason' => 'x'])->assertNotFound();
});

it('redirects to the ULID after creating a quotation', function () {
    $admin = admin();
    $customer = customer();

    $response = $this->actingAs($admin)->post(route('quotations.store'), [
        'customer_id' => $customer->id,
        'delivery_point' => 'Surabaya',
        'discount_pct' => 0,
        'ppn_rate' => 11,
        'lead_time_value' => 2,
        'lead_time_unit' => 'weeks',
        'validity_value' => 2,
        'validity_unit' => 'weeks',
        'payment_method' => 'invoice',
        'payment_terms_days' => 30,
        'dp_amount' => 0,
    ]);

    $response->assertRedirect(route('quotations.show', Quotation::sole()));
    expect($response->headers->get('Location'))->toContain(Quotation::sole()->ulid);
});

it('routes every other public record by ULID, never by the integer id', function () {
    $this->seed(DemoSeeder::class);
    $this->actingAs(User::where('username', 'capybara')->firstOrFail());

    $customer = Customer::firstOrFail();
    $inquiry = Inquiry::firstOrFail();
    $part = Part::firstOrFail();
    $uom = Uom::firstOrFail();
    $invoice = Invoice::firstOrFail();
    $product = CatalogProduct::firstOrFail();
    $type = CatalogProductType::firstOrFail();
    $keyAttr = CatalogProductTypeKeyAttribute::firstOrFail();

    foreach ([$customer, $inquiry, $part, $uom, $invoice, $product, $type, $keyAttr] as $record) {
        expect(Str::isUlid($record->ulid))->toBeTrue()
            ->and($record->ulid)->toBe(strtolower($record->ulid))
            ->and($record->getRouteKey())->toBe($record->ulid);
    }

    $this->get('/customers/'.$customer->ulid.'/edit')->assertOk();
    $this->get('/customers/'.$customer->id.'/edit')->assertNotFound();
    $this->get('/inquiries/'.$inquiry->ulid)->assertOk();
    $this->get('/inquiries/'.$inquiry->id)->assertNotFound();
    $this->post('/inquiries/'.$inquiry->id.'/reject', ['reason' => 'x'])->assertNotFound();
    $this->post('/parts/'.$part->id.'/stock', ['delta' => 1, 'reason' => 'x'])->assertNotFound();
    $this->put('/uoms/'.$uom->id, ['code' => 'X', 'name' => 'X'])->assertNotFound();
    $this->post('/invoices/'.$invoice->id.'/payments', ['amount' => 1])->assertNotFound();
    $this->post('/catalog/descriptions/'.$product->id.'/decision', ['decision' => 'reject'])->assertNotFound();
    $this->get('/catalog/types/'.$type->ulid)->assertOk();
    $this->get('/catalog/types/'.$type->id)->assertNotFound();
    $this->put('/catalog/types/attributes/'.$keyAttr->id, ['label' => 'X'])->assertNotFound();
});

it('preselects the customer for a new quotation by ULID', function () {
    $admin = admin();
    $customer = customer();

    $this->actingAs($admin)->get(route('quotations.create', ['customer' => $customer->ulid]))
        ->assertInertia(fn (Assert $page) => $page->where('preselectedCustomer', $customer->id));
    $this->actingAs($admin)->get(route('quotations.create', ['customer' => $customer->id]))
        ->assertInertia(fn (Assert $page) => $page->where('preselectedCustomer', null));
});

it('timestamps seeded ULIDs from the replay clock, not the build time', function () {
    $this->seed(DemoSeeder::class);

    foreach ([Quotation::class, Customer::class, Inquiry::class, Part::class, Uom::class, Invoice::class, CatalogProduct::class, CatalogProductType::class, CatalogProductTypeKeyAttribute::class] as $model) {
        $model::query()->when(method_exists($model, 'bootSoftDeletes'), fn ($q) => $q->withTrashed())->get()
            ->each(function ($record) {
                expect(intdiv(ulidMilliseconds($record->ulid), 1000))->toBe($record->created_at->getTimestamp());
            });
    }
});
