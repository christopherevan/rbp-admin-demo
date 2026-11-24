<?php

use App\Enums\Role;
use App\Models\Customer;
use App\Models\Part;
use App\Models\Quotation;
use App\Models\Uom;
use App\Models\User;
use App\Services\QuotationWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature/Domain', 'Feature/Http');

pest()->extend(TestCase::class)->in('Feature/Sandbox', 'Feature/Repository', 'Unit');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function admin(): User
{
    return User::factory()->admin()->create();
}

function salesRep(): User
{
    return User::factory()->create(['role' => Role::Sales]);
}

function customer(string $code = 'ACM'): Customer
{
    $customer = Customer::create(['code' => $code, 'name' => "PT {$code} Industri", 'address_line1' => 'Jl. Contoh 1', 'address_line2' => 'Jakarta', 'channel' => 'direct']);
    $customer->contacts()->create(['salutation' => 'ms', 'name' => 'Sari Test', 'email' => 'sari@acme.example']);

    return $customer;
}

function part(string $number = 'P-1', int $price = 100000, int $stock = 50, bool $stocked = true): Part
{
    $uom = Uom::firstOrCreate(['code' => 'PCS'], ['name' => 'Piece']);

    return Part::create(['part_number' => $number, 'description' => "Part {$number}", 'uom_id' => $uom->id, 'list_price' => $price, 'stocked' => $stocked, 'stock_qty' => $stock]);
}

/**
 * A quotation owned by $rep, optionally with lines ([Part, qty, unit price]).
 *
 * @param  list<array{Part, int, int}>  $lines
 * @param  array<string, mixed>  $terms
 */
function quotation(User $rep, array $lines = [], array $terms = []): Quotation
{
    $workflow = app(QuotationWorkflow::class);
    $customer = Customer::first() ?? customer();

    $quotation = $workflow->create([
        'customer_id' => $customer->id,
        'contact_id' => $customer->contacts()->value('id'),
        'sales_user_id' => $rep->id,
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
        ...$terms,
    ], $rep);

    if ($lines) {
        $workflow->saveLines($quotation, array_map(fn ($l) => ['part_id' => $l[0]->id, 'quantity' => $l[1], 'unit_price' => $l[2]], $lines), $rep);
    }

    return $quotation->fresh();
}

/** Move a quotation with lines through PO to ON PROGRESS. */
function confirmed(User $rep, array $lines, array $terms = []): Quotation
{
    $quotation = quotation($rep, $lines, $terms);
    app(QuotationWorkflow::class)->recordCustomerPo($quotation, 'PO-123', 'po.pdf', $rep);

    return $quotation->fresh();
}
