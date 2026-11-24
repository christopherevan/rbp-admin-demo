<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceType;
use App\Enums\QuotationStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\User;
use App\Services\QuotationWorkflow;
use App\Support\QuotationTotals;
use App\Support\TableQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class QuotationController extends Controller
{
    public function __construct(private QuotationWorkflow $workflow) {}

    public function index(Request $request): Response
    {
        $status = QuotationStatus::tryFrom((string) $request->query('status'));

        $query = Quotation::query()
            ->with(['customer:id,code,name', 'salesRep:id,name'])
            ->withSum('lines', 'line_total')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->boolean('mine'), fn ($q) => $q->where('sales_user_id', $request->user()->id));

        $quotations = TableQuery::paginate($query, $request, ['number', 'customer_po_number', 'customer.name'], ['number', 'created_at', 'updated_at'])
            ->through(fn (Quotation $quotation) => [
                'id' => $quotation->id,
                'ulid' => $quotation->ulid,
                'number' => $quotation->number,
                'customer' => $quotation->customer->name,
                'status' => $quotation->status,
                'total' => QuotationTotals::calculate((int) $quotation->lines_sum_line_total, $quotation->discount_pct, $quotation->ppn_rate)->grandTotal,
                'customer_po_number' => $quotation->customer_po_number,
                'sales_rep' => $quotation->salesRep->name,
                'created_at' => $quotation->created_at,
            ]);

        return Inertia::render('quotations/index', [
            'quotations' => $quotations,
            'filters' => [...$request->only(['q', 'sort', 'per_page']), 'status' => $status?->value, 'mine' => $request->boolean('mine')],
            'counts' => Quotation::query()
                ->when($request->boolean('mine'), fn ($q) => $q->where('sales_user_id', $request->user()->id))
                ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('quotations/form', [
            ...$this->formOptions(),
            'quotation' => null,
            'preselectedCustomer' => $request->filled('customer')
                ? Customer::where('ulid', (string) $request->string('customer'))->value('id')
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, $request->user());
        $quotation = $this->workflow->create($data, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Quotation {$quotation->number} created. Add the line items next."]);

        return redirect()->route('quotations.show', $quotation);
    }

    public function show(Request $request, Quotation $quotation): Response
    {
        $quotation->load([
            'customer', 'contact', 'salesRep:id,name', 'inquiry:id,ulid,number,quotation_id',
            'lines.part.uom', 'salesOrder.lines.part.uom', 'deliveryOrder',
            'invoices.payments.receiver:id,name', 'activities.user:id,name',
        ]);

        $user = $request->user();
        $downPayment = $quotation->invoices->firstWhere('type', InvoiceType::DownPayment);

        return Inertia::render('quotations/show', [
            'quotation' => [
                'id' => $quotation->id,
                'ulid' => $quotation->ulid,
                'number' => $quotation->number,
                'status' => $quotation->status,
                'customer' => [
                    'id' => $quotation->customer->id,
                    'ulid' => $quotation->customer->ulid,
                    'code' => $quotation->customer->code,
                    'name' => $quotation->customer->name,
                    'address' => $quotation->customer->address(),
                    'archived' => $quotation->customer->trashed(),
                ],
                'contact' => $quotation->contact ? [
                    'name' => $quotation->contact->displayName(),
                    'phone' => $quotation->contact->phone,
                    'email' => $quotation->contact->email,
                ] : null,
                'sales_rep' => $quotation->salesRep->only(['id', 'name']),
                'delivery_point' => $quotation->delivery_point,
                'discount_pct' => (float) $quotation->discount_pct,
                'ppn_rate' => $quotation->ppn_rate,
                'lead_time' => $quotation->lead_time_value.' '.$quotation->lead_time_unit,
                'validity' => $quotation->validity_value.' '.$quotation->validity_unit,
                'payment' => $quotation->payment_method === 'cod' ? 'Cash on delivery' : "Invoice, {$quotation->payment_terms_days} days",
                'dp_amount' => $quotation->dp_amount,
                'notes' => $quotation->notes,
                'customer_po_number' => $quotation->customer_po_number,
                'customer_po_file' => $quotation->customer_po_file,
                'customer_po_at' => $quotation->customer_po_at,
                'cancel_reason' => $quotation->cancel_reason,
                'cancelled_at' => $quotation->cancelled_at,
                'completed_at' => $quotation->completed_at,
                'created_at' => $quotation->created_at,
                'inquiry' => $quotation->inquiry?->only(['id', 'ulid', 'number']),
            ],
            'lines' => $quotation->lines->map(fn ($line) => [
                'id' => $line->id,
                'position' => $line->position,
                'part' => [
                    'id' => $line->part->id,
                    'part_number' => $line->part->part_number,
                    'description' => $line->part->description,
                    'uom' => $line->part->uom->code,
                    'list_price' => $line->part->list_price,
                    'stocked' => $line->part->stocked,
                    'stock_qty' => $line->part->stock_qty,
                    'discontinued' => $line->part->trashed(),
                ],
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'line_total' => $line->line_total,
            ]),
            'totals' => QuotationTotals::forQuotation($quotation)->toArray(),
            'salesOrder' => $quotation->salesOrder ? [
                'number' => $quotation->salesOrder->number,
                'created_at' => $quotation->salesOrder->created_at,
                'lines' => $quotation->salesOrder->lines->map(fn ($line) => [
                    'id' => $line->id,
                    'quotation_line_id' => $line->quotation_line_id,
                    'part_number' => $line->part->part_number,
                    'description' => $line->part->description,
                    'uom' => $line->part->uom->code,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'line_total' => $line->line_total,
                ]),
                'totals' => QuotationTotals::forSalesOrder($quotation, $downPayment ? $quotation->dp_amount : 0)?->toArray(),
            ] : null,
            'deliveryOrder' => $quotation->deliveryOrder?->only([
                'id', 'number', 'status', 'ship_to_name', 'ship_to_address', 'notes', 'delivered_at', 'created_at',
            ]),
            'invoices' => $quotation->invoices->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'ulid' => $invoice->ulid,
                'type' => $invoice->type,
                'number' => $invoice->number,
                'bill_to_name' => $invoice->bill_to_name,
                'amount_due' => $invoice->amount_due,
                'amount_paid' => $invoice->amountPaid(),
                'balance' => $invoice->balance(),
                'status' => $invoice->status,
                'paid_at' => $invoice->paid_at,
                'created_at' => $invoice->created_at,
                'payments' => $invoice->payments->map(fn ($payment) => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'note' => $payment->note,
                    'proof_file_name' => $payment->proof_file_name,
                    'received_at' => $payment->received_at,
                    'received_by' => $payment->receiver->name,
                ]),
            ]),
            'activities' => $quotation->activities->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user' => $log->user?->name,
                'created_at' => $log->created_at,
            ]),
            'progress' => $this->progress($quotation),
            'nextActions' => $this->workflow->nextActions($quotation, $user),
            'can' => [
                'update' => Gate::allows('update', $quotation),
                'fulfil' => Gate::allows('fulfil', $quotation),
                'bill' => Gate::allows('bill', $quotation),
            ],
            'defaults' => [
                'name' => $quotation->customer->name,
                'address' => $quotation->customer->address(),
            ],
            'maxLines' => config('demo.max_quotation_lines'),
        ]);
    }

    public function edit(Quotation $quotation): Response|RedirectResponse
    {
        Gate::authorize('update', $quotation);

        if (! $quotation->status->isOpenForEditing()) {
            Inertia::flash('toast', ['type' => 'info', 'message' => "Terms are locked once the customer's PO is in."]);

            return redirect()->route('quotations.show', $quotation);
        }

        return Inertia::render('quotations/form', [
            ...$this->formOptions(),
            'quotation' => [
                ...$quotation->only([
                    'id', 'ulid', 'number', 'customer_id', 'contact_id', 'sales_user_id', 'delivery_point', 'ppn_rate',
                    'lead_time_value', 'lead_time_unit', 'validity_value', 'validity_unit', 'payment_method',
                    'payment_terms_days', 'dp_amount', 'notes',
                ]),
                'discount_pct' => (float) $quotation->discount_pct,
            ],
            'preselectedCustomer' => null,
        ]);
    }

    public function update(Request $request, Quotation $quotation): RedirectResponse
    {
        Gate::authorize('update', $quotation);

        $data = $this->validated($request, $request->user(), $quotation);
        $this->workflow->update($quotation, $data, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Quotation terms saved.']);

        return redirect()->route('quotations.show', $quotation);
    }

    /**
     * Where the quotation is on the five-step stepper. A cancelled one shows
     * the step it had reached, worked out from its documents.
     *
     * @return array<string, mixed>
     */
    private function progress(Quotation $quotation): array
    {
        $final = $quotation->invoices->firstWhere('type', InvoiceType::Final);

        $reached = match (true) {
            $final && $final->isPaid() => 5,
            $final !== null => 4,
            $quotation->customer_po_at !== null => 3,
            $quotation->lines->isNotEmpty() => 2,
            default => 1,
        };

        return [
            'step' => $quotation->status->step() ?? $reached,
            'cancelled' => $quotation->status === QuotationStatus::Cancelled,
            'checks' => [
                'po' => $quotation->customer_po_at !== null,
                'so' => $quotation->salesOrder !== null,
                'do' => $quotation->deliveryOrder !== null,
                'delivered' => (bool) $quotation->deliveryOrder?->isDelivered(),
                'invoiced' => $final !== null,
                'paid' => (bool) $final?->isPaid(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'customers' => Customer::with('contacts:id,customer_id,salutation,name')
                ->orderBy('name')
                ->get(['id', 'code', 'name'])
                ->map(fn (Customer $customer) => [
                    'id' => $customer->id,
                    'code' => $customer->code,
                    'name' => $customer->name,
                    'contacts' => $customer->contacts->map(fn ($contact) => ['id' => $contact->id, 'name' => $contact->displayName()]),
                ]),
            'salesReps' => User::orderBy('name')->get(['id', 'name', 'role']),
            'deliveryPoints' => config('demo.delivery_points'),
            'ppnRate' => config('demo.ppn_rate'),
        ];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, User $user, ?Quotation $quotation = null): array
    {
        $customerId = $quotation->customer_id ?? $request->integer('customer_id');

        $data = $request->validate([
            'customer_id' => $quotation ? ['prohibited'] : ['required', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'contact_id' => ['nullable', Rule::exists('contacts', 'id')->where('customer_id', $customerId)->whereNull('deleted_at')],
            'sales_user_id' => ['nullable', Rule::exists('users', 'id')],
            'delivery_point' => ['required', Rule::in(config('demo.delivery_points'))],
            'discount_pct' => ['required', 'numeric', 'between:0,30'],
            'ppn_rate' => ['required', 'integer', Rule::in([0, (int) config('demo.ppn_rate')])],
            'lead_time_value' => ['required', 'integer', 'between:1,52'],
            'lead_time_unit' => ['required', Rule::in(Quotation::DURATION_UNITS)],
            'validity_value' => ['required', 'integer', 'between:1,90'],
            'validity_unit' => ['required', Rule::in(Quotation::DURATION_UNITS)],
            'payment_method' => ['required', Rule::in(Quotation::PAYMENT_METHODS)],
            'payment_terms_days' => ['required', 'integer', 'between:0,120'],
            'dp_amount' => ['required', 'integer', 'min:0', 'max:1000000000000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Sales reps write their own quotations; admins can assign anyone.
        if ($user->role !== Role::Admin) {
            $data['sales_user_id'] = $quotation->sales_user_id ?? $user->id;
        }

        $data['sales_user_id'] ??= $quotation->sales_user_id ?? $user->id;

        if ($data['payment_method'] === 'cod') {
            $data['payment_terms_days'] = 0;
        }

        return $data;
    }
}
