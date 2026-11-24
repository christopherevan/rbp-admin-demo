<?php

namespace Database\Seeders;

use App\Enums\DescriptionStatus;
use App\Enums\Role;
use App\Models\CatalogAttributeGroup;
use App\Models\CatalogCategory;
use App\Models\CatalogCategoryTypeMap;
use App\Models\CatalogProduct;
use App\Models\CatalogProductType;
use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Quotation;
use App\Models\Uom;
use App\Models\User;
use App\Services\CatalogTypeService;
use App\Services\DescriptionGuard;
use App\Services\InquiryWorkflow;
use App\Services\QuotationWorkflow;
use Carbon\CarbonImmutable;
use Database\Seeders\Demo\CatalogData;
use Database\Seeders\Demo\InquiryData;
use Database\Seeders\Demo\SalesData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Four months of fictional history for the demo template.
 *
 * Each quotation and inquiry is first planned as a timeline of dated events
 * (created, priced, PO, sales order, delivery, invoice, payments). Then all
 * events are sorted and replayed through the real QuotationWorkflow and
 * InquiryWorkflow with the clock set to each event's time. Document numbers,
 * statuses, totals, stock and the audit log therefore come out exactly as
 * the app produces them; the seeder doubles as an end-to-end test.
 *
 * Dates are relative to now(); everything else is seeded from fixed values.
 * Log in as demo / demo1234.
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'demo1234';

    private CarbonImmutable $now;

    /** @var array<string, User> */
    private array $users = [];

    /** @var array<string, Part> */
    private array $parts = [];

    /** @var array<string, Customer> */
    private array $customers = [];

    public function __construct(
        private QuotationWorkflow $quotations,
        private InquiryWorkflow $inquiries,
        private DescriptionGuard $guard,
        private CatalogTypeService $catalogTypes,
    ) {}

    public function run(): void
    {
        $this->refuseRealDatabases();

        mt_srand(20261027);
        $this->now = CarbonImmutable::now();

        try {
            // Master data has existed for a year.
            Date::setTestNow($this->now->subYear()->startOfMonth()->setTime(9, 0));

            $this->seedUsers();
            $this->seedPartsAndUnits();
            $this->seedCustomers();

            $events = array_merge(
                ...array_map(fn (array $plan) => $plan['events'], $this->planQuotations()),
                ...array_map(fn (array $plan) => $plan['events'], $this->planInquiries()),
            );

            usort($events, fn ($a, $b) => $a['at'] <=> $b['at']);

            foreach ($events as $event) {
                Date::setTestNow($event['at']);
                ($event['run'])();
            }

            // The archived customer closed down after its last order, and the
            // part only it bought was discontinued.
            Date::setTestNow($this->now->subDays(20)->setTime(10, 0));
            $this->customers[SalesData::ARCHIVED[0]]->delete();
            $this->parts[SalesData::DISCONTINUED[0]]->delete();

            Date::setTestNow($this->now->subDays(21)->setTime(1, 40));
            $this->seedCatalog();
        } finally {
            Date::setTestNow();
        }

        foreach (SalesData::LOW_STOCK as $partNumber => $stock) {
            Part::where('part_number', $partNumber)->update(['stock_qty' => $stock]);
        }

        $this->command?->info(sprintf(
            'Demo data: %d customers, %d parts, %d quotations, %d inquiries, %d AI drafts.',
            Customer::withTrashed()->count(),
            Part::withTrashed()->count(),
            Quotation::count(),
            Inquiry::count(),
            CatalogProduct::count(),
        ));
    }

    /**
     * Only the template build (or the test suite) may run this: it assumes
     * an empty database and would duplicate numbering in a used one.
     */
    private function refuseRealDatabases(): void
    {
        if (DB::getDefaultConnection() !== 'template' && ! app()->runningUnitTests()) {
            throw new RuntimeException('The demo seeder only runs through `php artisan demo:build-template`.');
        }

        if (DB::table('users')->exists()) {
            throw new RuntimeException('The database already has data. The demo seeder needs an empty one.');
        }
    }

    // ---------------------------------------------------------------------
    // Reference data
    // ---------------------------------------------------------------------

    private function seedUsers(): void
    {
        $password = Hash::make(self::PASSWORD);

        foreach (SalesData::USERS as $username => [$name, $role]) {
            $this->users[$username] = User::create([
                'username' => $username,
                'name' => $name,
                'role' => Role::from($role),
                'password' => $password,
            ]);
        }
    }

    private function seedPartsAndUnits(): void
    {
        $uoms = [];

        foreach (SalesData::UOMS as $code => $name) {
            $uoms[$code] = Uom::create(['code' => $code, 'name' => $name]);
        }

        foreach (SalesData::PARTS + [SalesData::DISCONTINUED[0] => array_slice(SalesData::DISCONTINUED, 1)] as $number => [$description, $uom, $price, $kind]) {
            [$min, $max] = SalesData::STOCK[$kind];

            $this->parts[$number] = Part::create([
                'part_number' => $number,
                'description' => $description,
                'uom_id' => $uoms[$uom]->id,
                'list_price' => $price,
                'stocked' => $kind !== 'service',
                'stock_qty' => $max ? mt_rand($min, $max) : 0,
            ]);
        }
    }

    private function seedCustomers(): void
    {
        foreach (SalesData::CUSTOMERS + [SalesData::ARCHIVED[0] => array_slice(SalesData::ARCHIVED, 1)] as $code => [$name, $line1, $line2, , $notes, $channel]) {
            $customer = Customer::create([
                'code' => $code,
                'name' => $name,
                'address_line1' => $line1,
                'address_line2' => $line2,
                'channel' => $channel,
                'notes' => $notes,
            ]);

            $words = array_slice(explode(' ', preg_replace('/^(PT|CV|UD|Workshop)\s+/', '', $name)), 0, 2);
            $domain = strtolower(implode('', $words)).'.example';

            $animals = SalesData::CONTACT_ANIMALS;

            for ($i = 0, $count = $channel === 'marketplace' ? 1 : mt_rand(1, 3); $i < $count; $i++) {
                // Draw without replacement so one customer never has two of the same animal.
                [$animal] = array_splice($animals, mt_rand(0, count($animals) - 1), 1);

                $customer->contacts()->create([
                    'salutation' => mt_rand(0, 1) ? 'ms' : 'mr',
                    'name' => "Anonymous {$animal}",
                    'phone' => sprintf('+62 8%02d 0000 %04d', mt_rand(11, 99), mt_rand(1000, 9999)),
                    'email' => strtolower("{$animal}@{$domain}"),
                ]);
            }

            $this->customers[$code] = $customer;
        }
    }

    // ---------------------------------------------------------------------
    // Quotations
    // ---------------------------------------------------------------------

    /** [status, sub-stage, force a down payment] for every quotation. */
    private function stages(): array
    {
        return [
            ['not_submitted', null, false], ['not_submitted', null, false],
            ...array_fill(0, 6, ['waiting_customer', null, false]),
            ['on_progress', 'po', true], ['on_progress', 'po', false], ['on_progress', 'so', false],
            ['on_progress', 'so_partial', false], ['on_progress', 'do', false], ['on_progress', 'delivered', true],
            ['waiting_payment', 'unpaid', false], ['waiting_payment', 'unpaid', false],
            ['waiting_payment', 'partial', false], ['waiting_payment', 'partial', true],
            ['completed', null, true], ...array_fill(0, 5, ['completed', null, false]),
            ['cancelled', null, false], ['cancelled', null, false],
        ];
    }

    private function planQuotations(): array
    {
        $weighted = [];

        foreach (SalesData::CUSTOMERS as $code => $c) {
            array_push($weighted, ...array_fill(0, $c[7], $code));
        }

        $plans = [];

        foreach ($this->stages() as [$status, $stage, $dp]) {
            $plans[] = $this->planQuotation($weighted[mt_rand(0, count($weighted) - 1)], $status, $stage, $dp);
        }

        // The archived customer's history, from before it closed.
        $plans[] = $this->planQuotation(SalesData::ARCHIVED[0], 'completed', null, false, 110, true);

        return $plans;
    }

    private function planQuotation(string $code, string $status, ?string $stage, bool $forceDp, ?int $offsetDays = null, bool $discontinuedPart = false): array
    {
        $c = (SalesData::CUSTOMERS + [SalesData::ARCHIVED[0] => array_slice(SalesData::ARCHIVED, 1)])[$code];
        $customer = $this->customers[$code];
        $marketplace = $c[5] === 'marketplace';
        $sales = mt_rand(1, 100) <= 80 && $c[8] !== 'capybara' ? $c[8] : ['narwhal', 'axolotl', 'quokka'][mt_rand(0, 2)];

        $lines = $this->lines($c[6][mt_rand(0, count($c[6]) - 1)], $marketplace);

        if ($discontinuedPart) {
            $lines = [SalesData::DISCONTINUED[0] => ['quantity' => 24, 'unit_price' => SalesData::DISCONTINUED[3]]] + $lines;
        }

        $cod = $marketplace || mt_rand(1, 100) <= 8;
        $discount = $marketplace ? 0 : [0, 0, 0, 0, 0, 2.5, 5, 5, 7.5, 10][mt_rand(0, 9)];
        $subtotal = array_sum(array_map(fn ($l) => $l['unit_price'] * $l['quantity'], $lines));
        $grand = ($subtotal - $subtotal * $discount / 100) * (1 + config('demo.ppn_rate') / 100);

        $dp = ! $cod && ($forceDp || ($status !== 'not_submitted' && mt_rand(1, 100) <= 15));
        [$leadValue, $leadUnit] = [[3, 'days'], [5, 'days'], [7, 'days'], [14, 'days'], [2, 'weeks'], [3, 'weeks'], [4, 'weeks'], [6, 'weeks']][mt_rand(0, 7)];

        $plan = [
            'customer' => $customer,
            'sales' => $sales,
            'status' => $status,
            'stage' => $stage,
            'lines' => $lines,
            'terms' => [
                'customer_id' => $customer->id,
                'contact_id' => $this->pick($customer->contacts()->pluck('id')->all()),
                'sales_user_id' => $this->users[$sales]->id,
                'delivery_point' => $c[3],
                'discount_pct' => $discount,
                'ppn_rate' => config('demo.ppn_rate'),
                'lead_time_value' => $leadValue,
                'lead_time_unit' => $leadUnit,
                'validity_value' => [2, 2, 14, 30][mt_rand(0, 3)],
                'validity_unit' => 'weeks',
                'payment_method' => $cod ? 'cod' : 'invoice',
                'payment_terms_days' => $cod ? 0 : [30, 30, 30, 14, 45][mt_rand(0, 4)],
                'dp_amount' => $dp ? (int) round($grand * [0.3, 0.5][mt_rand(0, 1)], -5) : 0,
                'notes' => mt_rand(1, 100) <= 45 ? SalesData::QUOTATION_NOTES[mt_rand(0, count(SalesData::QUOTATION_NOTES) - 1)] : null,
            ],
        ];

        $plan['terms']['validity_unit'] = $plan['terms']['validity_value'] > 4 ? 'days' : 'weeks';
        $plan['events'] = $this->quotationEvents($plan, $offsetDays, $leadUnit === 'weeks' ? $leadValue * 7 : $leadValue);

        return $plan;
    }

    /** @return array<string, array{quantity: int, unit_price: int}> */
    private function lines(string $theme, bool $small): array
    {
        $lines = [];

        foreach (SalesData::THEMES[$theme] as [$candidates, $chance]) {
            if (mt_rand(1, 100) > $chance * 100) {
                continue;
            }

            $number = $candidates[mt_rand(0, count($candidates) - 1)];
            [, , $price, $kind] = SalesData::PARTS[$number];
            [$min, $max, $step] = SalesData::QUANTITIES[$kind];

            if ($small) {
                $max = max($min, (int) ceil($max / 4));
            }

            // Negotiated per quotation: a few percent either side of list.
            $lines[$number] = [
                'quantity' => $min + $step * mt_rand(0, intdiv($max - $min, $step)),
                'unit_price' => (int) round($price * mt_rand(94, 106) / 100, -3),
            ];
        }

        return $lines;
    }

    /**
     * The quotation's history as dated, runnable events. Built forward from
     * a start date, then slid back whole weeks until the last event is in
     * the past, so everything keeps its weekday and office hours.
     */
    private function quotationEvents(array $plan, ?int $offsetDays, int $leadDays): array
    {
        $status = $plan['status'];
        $stage = $plan['stage'];
        $sales = $plan['sales'];
        $admin = fn () => mt_rand(1, 100) <= 65 ? 'capybara' : 'demo';
        $state = new \ArrayObject;

        $offsetDays ??= match ($status) {
            'not_submitted' => mt_rand(0, 4),
            'waiting_customer' => mt_rand(1, 45),
            'on_progress' => mt_rand(10, 40),
            'waiting_payment' => mt_rand(35, 70),
            'completed' => mt_rand(55, 115),
            'cancelled' => mt_rand(20, 100),
        };

        $t = $this->start($offsetDays);
        $events = [];

        $add = function (CarbonImmutable $at, string $by, callable $run) use (&$events) {
            $events[] = ['at' => $at, 'by' => $by, 'run' => $run];
        };

        $add($t, $sales, function () use ($plan, $sales, $state) {
            $state['q'] = $this->quotations->create($plan['terms'], $this->users[$sales]);
        });

        if ($status === 'not_submitted') {
            return $this->intoThePast($events);
        }

        $lines = fn (bool $revised = false) => array_values(array_map(fn ($number, $line) => [
            'part_id' => $this->parts[$number]->id,
            'quantity' => $line['quantity'],
            'unit_price' => $revised ? (int) round($line['unit_price'] * 0.98, -3) : $line['unit_price'],
        ], array_keys($plan['lines']), $plan['lines']));

        $t = $this->later($t, 15, 120);
        $add($t, $sales, fn () => $this->quotations->saveLines($state['q'], $lines(), $this->users[$sales]));

        // Many quotations are revised once before the customer answers.
        if (mt_rand(1, 100) <= 35) {
            $t = $this->later($t, 60 * 20, 60 * 24 * 5);
            $add($t, $sales, fn () => $this->quotations->saveLines($state['q'], $lines(true), $this->users[$sales]));
        }

        if ($status === 'waiting_customer') {
            return $this->intoThePast($events);
        }

        if ($status === 'cancelled') {
            $t = $this->later($t, 60 * 24 * 4, 60 * 24 * 25);
            $reason = SalesData::CANCEL_REASONS[mt_rand(0, count(SalesData::CANCEL_REASONS) - 1)];
            $add($t, $sales, fn () => $this->quotations->cancel($state['q'], $reason, $this->users[$sales]));

            return $this->intoThePast($events);
        }

        $t = $this->later($t, 60 * 24 * 2, 60 * 24 * 14);
        $poBy = mt_rand(1, 100) <= 60 ? $sales : $admin();
        $poNumber = $this->poNumber($plan['customer']->code, $t);
        $add($t, $poBy, fn () => $this->quotations->recordCustomerPo($state['q'], $poNumber, 'PO '.str_replace('/', '-', $poNumber).'.pdf', $this->users[$poBy]));

        $billTo = ['bill_to_name' => $plan['customer']->name, 'bill_to_address' => $plan['customer']->address()];
        $dpAt = null;

        if ($plan['terms']['dp_amount'] > 0) {
            $dpAt = $this->later($t, 30, 60 * 24);
            $by = $admin();
            $add($dpAt, $by, function () use ($state, $billTo, $by) {
                $state['dp'] = $this->quotations->createDownPaymentInvoice($state['q'], $billTo, $this->users[$by]);
            });
        }

        if ($stage === 'po') {
            return $this->intoThePast($events);
        }

        if ($dpAt) {
            $by = $admin();
            $note = $this->paymentNote();
            $add($this->later($dpAt, 60 * 24, 60 * 24 * 7), $by, fn () => $this->pay($state['dp'], null, $note, $by));
        }

        $t = $this->later($t, 60, 60 * 24 * 2);
        $by = $admin();
        $add($t, $by, function () use ($state, $stage, $by) {
            $lines = $state['q']->lines()->get();
            $selection = $lines->mapWithKeys(fn ($line) => [$line->id => $line->quantity])->all();

            // A partial order: the customer drops the last line and halves the first.
            if ($stage === 'so_partial' && $lines->count() > 1) {
                array_pop($selection);
                $first = $lines->first();
                $selection[$first->id] = max(1, intdiv($first->quantity, 2));
            }

            $this->quotations->createSalesOrder($state['q'], $selection, $this->users[$by]);
        });

        if (in_array($stage, ['so', 'so_partial'], true)) {
            return $this->intoThePast($events);
        }

        $t = $this->later($t, 60 * 24 * max(1, $leadDays - 2), 60 * 24 * ($leadDays + 4));
        $by = $admin();
        $doNote = mt_rand(1, 100) <= 50 ? SalesData::DO_NOTES[mt_rand(0, count(SalesData::DO_NOTES) - 1)] : null;
        $add($t, $by, fn () => $this->quotations->createDeliveryOrder($state['q'], [
            'ship_to_name' => $plan['customer']->name,
            'ship_to_address' => $plan['customer']->address(),
            'notes' => $doNote,
        ], $this->users[$by]));

        if ($stage === 'do') {
            return $this->intoThePast($events);
        }

        $t = $this->later($t, 60 * 24, 60 * 24 * 4);
        $by = $admin();
        $add($t, $by, fn () => $this->quotations->completeDelivery($state['q']->deliveryOrder()->first(), $this->users[$by]));

        if ($stage === 'delivered') {
            return $this->intoThePast($events);
        }

        $t = $this->later($t, 30, 60 * 24 * 2);
        $by = $admin();
        $add($t, $by, function () use ($state, $billTo, $by) {
            $state['invoice'] = $this->quotations->createFinalInvoice($state['q'], $billTo, $this->users[$by]);
        });

        if ($stage === 'unpaid') {
            return $this->intoThePast($events);
        }

        // Partly paid, settled in one transfer, or settled in two.
        $split = $stage === 'partial' || mt_rand(1, 100) <= 40;
        $t = $this->later($t, 60 * 24 * 10, 60 * 24 * 25);
        $by = $admin();
        $note = $this->paymentNote();
        $share = $split ? mt_rand(40, 60) / 100 : null;
        $add($t, $by, fn () => $this->pay($state['invoice'], $share, $note, $by));

        if ($split && $stage !== 'partial') {
            $by = $admin();
            $note = $this->paymentNote();
            $add($this->later($t, 60 * 24 * 7, 60 * 24 * 20), $by, fn () => $this->pay($state['invoice'], null, $note, $by));
        }

        return $this->intoThePast($events);
    }

    /** Pay a share of the balance (rounded to Rp 100.000), or all of it. */
    private function pay(Invoice $invoice, ?float $share, string $note, string $by): void
    {
        $invoice->refresh();
        $balance = $invoice->balance();
        $amount = $share === null ? $balance : min($balance - 100000, max(100000, (int) round($balance * $share, -5)));

        $this->quotations->recordPayment($invoice, $amount, $note, 'transfer-'.substr(md5($invoice->number.$amount), 0, 6).'.jpg', $this->users[$by]);
    }

    private function pick(array $items): mixed
    {
        return $items ? $items[mt_rand(0, count($items) - 1)] : null;
    }

    private function paymentNote(): string
    {
        return SalesData::PAYMENT_NOTES[mt_rand(0, count(SalesData::PAYMENT_NOTES) - 1)];
    }

    /** Each customer's purchasing department has its own PO format. */
    private function poNumber(string $code, CarbonImmutable $at): string
    {
        return match (crc32($code) % 3) {
            0 => sprintf('PO/%s/%s/%04d', $code, $at->format('ym'), mt_rand(100, 1999)),
            1 => sprintf('45%08d', mt_rand(10000000, 99999999)),
            default => sprintf('%03d/PO-%s/%s', mt_rand(1, 350), $code, $at->format('Y')),
        };
    }

    // ---------------------------------------------------------------------
    // Inquiries
    // ---------------------------------------------------------------------

    private function planInquiries(): array
    {
        return array_map(function (array $spec) {
            $state = new \ArrayObject;
            $customer = is_string($spec['from']) ? $this->customers[$spec['from']] : null;
            $contact = $customer?->contacts()->first();

            [$name, $company, $email, $phone, $city] = $customer
                ? [$contact->name, $customer->name, $contact->email, $contact->phone, SalesData::CUSTOMERS[$spec['from']][3]]
                : $spec['from'];

            $items = array_map(function (array $line) {
                [$what, $quantity, $unit, $note] = $line;
                $part = $this->parts[$what] ?? null;

                return [
                    'part_id' => $part?->id,
                    'title' => $part->description ?? $what,
                    'part_number' => $part?->part_number,
                    'quantity' => $quantity,
                    'unit' => $unit,
                    'note' => $note,
                ];
            }, $spec['lines']);

            $t = $this->start($spec['days'], $spec['days'] === 0);
            $events = [['at' => $t, 'run' => function () use ($state, $name, $company, $email, $phone, $city, $spec, $items) {
                $state['inquiry'] = $this->inquiries->submit(compact('name', 'company', 'email', 'phone', 'city') + [
                    'message' => $spec['message'],
                    'source' => $spec['source'],
                ], $items);
            }]];

            if (isset($spec['assign'])) {
                $t = $this->later($t, 30, 60 * 6);
                $assignee = $this->users[$spec['assign']];
                $events[] = ['at' => $t, 'run' => fn () => $this->inquiries->assign($state['inquiry'], $assignee, $this->users['capybara'])];
            }

            if ($spec['link'] ?? false) {
                $t = $this->later($t, 10, 90);
                $by = $this->users[$spec['assign']];
                $events[] = ['at' => $t, 'run' => fn () => $this->inquiries->linkCustomer($state['inquiry'], $customer, $contact, $by)];
            }

            if (isset($spec['decide'])) {
                $t = $this->later($t, 60, 60 * 24 * 2);
                $by = $this->users[$spec['decide']];
                $events[] = ['at' => $t, 'run' => $spec['status'] === 'rejected'
                    ? fn () => $this->inquiries->reject($state['inquiry'], $spec['reason'], $by)
                    : fn () => $this->inquiries->approve($state['inquiry'], $by)];
            }

            if ($spec['status'] === 'quoted') {
                $t = $this->later($t, 30, 60 * 4);
                $by = $this->users[$spec['assign']];
                $events[] = ['at' => $t, 'run' => function () use ($state, $by) {
                    $state['quotation'] = $this->inquiries->convert($state['inquiry'], $by);
                }];

                // The rep reviews the drafted prices and sends it the next day.
                $t = $this->later($t, 60 * 18, 60 * 26);
                $events[] = ['at' => $t, 'run' => function () use ($state, $by) {
                    $lines = $state['quotation']->lines()->get()->map(fn ($line) => [
                        'part_id' => $line->part_id,
                        'quantity' => $line->quantity,
                        'unit_price' => (int) round($line->unit_price * 0.97, -3),
                    ])->all();

                    $this->quotations->saveLines($state['quotation'], $lines, $by);
                }];
            }

            return ['events' => $this->intoThePast($events)];
        }, InquiryData::all());
    }

    // ---------------------------------------------------------------------
    // AI description review queue
    // ---------------------------------------------------------------------

    private function seedCatalog(): void
    {
        foreach (CatalogData::categories() as $cat) {
            CatalogCategory::create($cat);
        }

        /** @var array<string, CatalogAttributeGroup> $groups */
        $groups = [];
        foreach (CatalogData::attributeGroups() as $group) {
            $groups[$group['supplier'].'|'.$group['label']] = CatalogAttributeGroup::create($group);
        }

        foreach (CatalogData::types() as $index => $spec) {
            $typeTime = $this->now->subDays(30)->setTime(10, $index * 5);
            Date::setTestNow($typeTime);

            $type = CatalogProductType::create([
                'name' => $spec['name'],
                'slug' => $spec['slug'],
                'header_image' => $spec['header_image'],
                'sort_order' => $spec['sort_order'],
            ]);

            foreach ($spec['categories'] as [$supplier, $path]) {
                CatalogCategoryTypeMap::create([
                    'supplier' => $supplier,
                    'category_path' => $path,
                    'product_type_id' => $type->id,
                ]);
            }

            foreach ($spec['key_attributes'] as $pos => $attrSpec) {
                $attrTime = $typeTime->addMinutes($pos + 1);
                Date::setTestNow($attrTime);

                $keyAttribute = $type->keyAttributes()->create([
                    'label' => $attrSpec['label'],
                    'unit' => $attrSpec['unit'],
                    'position' => $pos + 1,
                ]);

                $groupIds = array_map(
                    fn (array $ref): int => $groups[$ref[0].'|'.$ref[1]]->id,
                    $attrSpec['groups'],
                );
                $keyAttribute->attributeGroups()->sync($groupIds);
            }
        }

        $batch = 'msgbatch_demo'.substr(md5((string) $this->now->format('Ymd')), 0, 18);

        foreach (CatalogData::all() as $i => $p) {
            $check = $this->guard->check($p['en'], $p['facts'], $p['family'], $p['title'], $p['manufacturer']);
            $generatedAt = $this->now->subDays(21)->setTime(1, 40)->addMinutes($i * 3);
            Date::setTestNow($generatedAt);

            $product = new CatalogProduct([
                'supplier' => $p['supplier'],
                'title' => $p['title'],
                'category' => $p['category'],
                'family' => $p['family'],
                'facts' => $p['facts'],
                'manufacturer_text' => $p['manufacturer'],
                'description_en' => $p['en'],
                'description_id' => $p['id'],
                'meta_en' => $p['meta'],
                'ai_status' => DescriptionStatus::from($p['review']),
                'ai_findings' => ['errors' => $check['errors'], 'warnings' => $check['warnings']],
                'ai_meta' => [
                    'model' => 'claude-haiku-4-5',
                    'batch_id' => $batch,
                    'generated_at' => $generatedAt->toIso8601String(),
                    'similarity' => $check['similarity'],
                    'confidence' => $check['errors'] ? 'medium' : 'high',
                    'usage' => ['input_tokens' => 1850 + ($i * 131) % 1400, 'output_tokens' => 610 + ($i * 57) % 260],
                ],
            ]);

            if ($p['review'] !== 'pending') {
                $product->decided_by = $this->users[$i % 2 ? 'demo' : 'capybara']->id;
                $product->decided_at = $generatedAt->addDays(2 + $i)->setTime(9 + $i, 15);
                $product->rejection_note = $p['note'] ?? null;
            }

            $product->created_at = $generatedAt;
            $product->updated_at = $product->decided_at ?? $generatedAt;
            $product->save();
        }

        Date::setTestNow($this->now->subMinutes(2));
        $this->catalogTypes->apply();
    }

    // ---------------------------------------------------------------------
    // Time
    // ---------------------------------------------------------------------

    private function intoThePast(array $events): array
    {
        $cutoff = $this->now->subMinutes(30);

        usort($events, fn ($a, $b) => $a['at'] <=> $b['at']);

        while (end($events)['at']->gt($cutoff)) {
            foreach ($events as &$event) {
                $event['at'] = $event['at']->subWeek();
            }
            unset($event);
        }

        return $events;
    }

    /**
     * A working-day morning or afternoon `$offsetDays` back, stepping
     * further back rather than forward: forward from a weekend is the future.
     * Today's events may fall on any time already past.
     */
    private function start(int $offsetDays, bool $today = false): CarbonImmutable
    {
        if ($today) {
            return $this->now->subMinutes(mt_rand(40, 180));
        }

        $t = $this->now->subDays($offsetDays)->setTime(mt_rand(8, 16), mt_rand(0, 59));

        while ($t->isWeekend() || $t->gt($this->now->subHours(2))) {
            $t = $t->subDay();
        }

        return $t;
    }

    private function later(CarbonImmutable $from, int $minMinutes, int $maxMinutes): CarbonImmutable
    {
        return $this->office($from->addMinutes(mt_rand($minMinutes, $maxMinutes)));
    }

    /** Moved forward into Monday-Friday, 08:00-17:00. */
    private function office(CarbonImmutable $t): CarbonImmutable
    {
        if ($t->hour >= 17) {
            $t = $t->addDay()->setTime(mt_rand(8, 10), mt_rand(0, 59));
        } elseif ($t->hour < 8) {
            $t = $t->setTime(mt_rand(8, 10), mt_rand(0, 59));
        }

        while ($t->isWeekend()) {
            $t = $t->addDay()->setTime(mt_rand(8, 10), mt_rand(0, 59));
        }

        return $t;
    }
}
