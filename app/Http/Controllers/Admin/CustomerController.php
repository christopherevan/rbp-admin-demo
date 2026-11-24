<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\QuotationLine;
use App\Support\TableQuery;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $archived = $request->boolean('archived');

        $query = Customer::query()
            ->with('contacts:id,customer_id,name,phone,email')
            ->withCount('quotations')
            ->when($archived, fn ($q) => $q->onlyTrashed());

        $customers = TableQuery::paginate($query, $request, ['code', 'name', 'contacts.name'], ['code', 'name', 'updated_at', 'created_at'], 'name')
            ->through(fn (Customer $customer) => [
                'id' => $customer->id,
                'ulid' => $customer->ulid,
                'code' => $customer->code,
                'name' => $customer->name,
                'address' => $customer->address(),
                'channel' => $customer->channel,
                'contacts' => $customer->contacts->map->only(['name', 'phone', 'email']),
                'quotations_count' => $customer->quotations_count,
                'updated_at' => $customer->updated_at,
                'archived' => $customer->trashed(),
            ]);

        return Inertia::render('customers/index', [
            'customers' => $customers,
            'filters' => [...$request->only(['q', 'sort', 'per_page']), 'archived' => $archived],
            'archivedCount' => Customer::onlyTrashed()->count(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('customers/form', [
            'customer' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $customer = DB::transaction(function () use ($data) {
            $customer = Customer::create($data);
            $customer->contacts()->createMany($data['contacts'] ?? []);

            return $customer;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$customer->name} added."]);

        return redirect()->route('customers.edit', $customer);
    }

    public function edit(Customer $customer): Response
    {
        $customer->load('contacts');

        return Inertia::render('customers/form', [
            'customer' => [
                ...$customer->only(['id', 'ulid', 'code', 'name', 'address_line1', 'address_line2', 'channel', 'notes']),
                'archived' => $customer->trashed(),
                'contacts' => $customer->contacts->map->only(['id', 'salutation', 'name', 'phone', 'email']),
            ],
            'quotations' => $customer->quotations()->with('salesRep:id,name')->latest('id')->limit(10)->get()
                ->map(fn ($q) => [
                    'id' => $q->id,
                    'ulid' => $q->ulid,
                    'number' => $q->number,
                    'status' => $q->status,
                    'sales_rep' => $q->salesRep->name,
                    'created_at' => $q->created_at,
                ]),
            // The original's "customer report": every line ever quoted to them.
            'quotedLines' => QuotationLine::query()
                ->with('part:id,part_number,description')
                ->join('quotations', 'quotations.id', '=', 'quotation_lines.quotation_id')
                ->where('quotations.customer_id', $customer->id)
                ->orderByDesc('quotations.created_at')
                ->limit(50)
                ->get(['quotation_lines.*', 'quotations.ulid as quotation_ulid', 'quotations.number as quotation_number', 'quotations.created_at as quoted_at'])
                ->map(fn ($line) => [
                    'id' => $line->id,
                    'quotation_id' => $line->quotation_id,
                    'quotation_ulid' => $line->quotation_ulid,
                    'quotation_number' => $line->quotation_number,
                    'quoted_at' => CarbonImmutable::parse($line->quoted_at)->toIso8601String(),
                    'part_number' => $line->part->part_number,
                    'description' => $line->part->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                ]),
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $data = $this->validated($request, $customer);

        DB::transaction(function () use ($customer, $data) {
            $customer->update($data);

            // Contacts quotations already point at are kept (soft-deleted)
            // rather than removed, so old documents still show who it was.
            $keep = collect($data['contacts'] ?? [])->pluck('id')->filter()->all();
            $customer->contacts()->whereNotIn('id', $keep)->get()->each->delete();

            foreach ($data['contacts'] ?? [] as $contact) {
                if (! empty($contact['id'])) {
                    Contact::whereKey($contact['id'])->where('customer_id', $customer->id)->update(collect($contact)->except('id')->all());
                } else {
                    $customer->contacts()->create($contact);
                }
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Customer saved.']);

        return back();
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        Gate::authorize('archive-records');

        $customer->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$customer->name} archived. Their quotations keep their history."]);

        return redirect()->route('customers.index');
    }

    public function restore(Customer $customer): RedirectResponse
    {
        Gate::authorize('archive-records');

        $customer->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$customer->name} restored."]);

        return back();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Customer $customer = null): array
    {
        $request->merge(['code' => strtoupper((string) $request->input('code'))]);

        return $request->validate([
            'code' => ['required', 'regex:/^[A-Z]{2,5}$/', Rule::unique('customers', 'code')->ignore($customer)],
            'name' => ['required', 'string', 'max:120'],
            'address_line1' => ['nullable', 'string', 'max:160'],
            'address_line2' => ['nullable', 'string', 'max:160'],
            'channel' => ['required', Rule::in(Customer::CHANNELS)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'contacts' => ['array', 'max:5'],
            'contacts.*.id' => ['nullable', 'integer'],
            'contacts.*.salutation' => ['required', Rule::in(['mr', 'ms'])],
            'contacts.*.name' => ['required', 'string', 'max:80'],
            'contacts.*.phone' => ['nullable', 'string', 'max:30'],
            'contacts.*.email' => ['nullable', 'email', 'max:120'],
        ], [
            'code.regex' => 'Use 2 to 5 letters, e.g. SPL. It appears in every document number.',
        ]);
    }
}
