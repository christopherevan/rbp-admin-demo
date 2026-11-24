<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\User;
use App\Services\InquiryWorkflow;
use App\Support\TableQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InquiryController extends Controller
{
    public function __construct(private InquiryWorkflow $workflow) {}

    public function index(Request $request): Response
    {
        $status = InquiryStatus::tryFrom((string) $request->query('status'));

        $query = Inquiry::query()
            ->with('assignee:id,name')
            ->withCount('items')
            ->when($status, fn ($q) => $q->where('status', $status));

        $inquiries = TableQuery::paginate($query, $request, ['number', 'name', 'company', 'email'], ['number', 'created_at', 'updated_at'])
            ->through(fn (Inquiry $inquiry) => [
                'id' => $inquiry->id,
                'ulid' => $inquiry->ulid,
                'number' => $inquiry->number,
                'name' => $inquiry->name,
                'company' => $inquiry->company,
                'email' => $inquiry->email,
                'phone' => $inquiry->phone,
                'source' => $inquiry->source,
                'items_count' => $inquiry->items_count,
                'status' => $inquiry->status,
                'assignee' => $inquiry->assignee?->name,
                'created_at' => $inquiry->created_at,
            ]);

        return Inertia::render('inquiries/index', [
            'inquiries' => $inquiries,
            'filters' => [...$request->only(['q', 'sort', 'per_page']), 'status' => $status?->value],
            'counts' => Inquiry::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(Inquiry $inquiry): Response
    {
        $inquiry->load(['items.part.uom', 'assignee:id,name', 'decider:id,name', 'customer', 'contact', 'quotation:id,ulid,number,status', 'activities.user:id,name']);

        return Inertia::render('inquiries/show', [
            'inquiry' => [
                ...$inquiry->only(['id', 'ulid', 'number', 'name', 'company', 'email', 'phone', 'city', 'message', 'source', 'status', 'rejection_reason', 'decided_at', 'created_at']),
                'assignee' => $inquiry->assignee?->only(['id', 'name']),
                'decider' => $inquiry->decider?->name,
                'customer' => $inquiry->customer ? ['id' => $inquiry->customer->id, 'ulid' => $inquiry->customer->ulid, 'name' => $inquiry->customer->name, 'code' => $inquiry->customer->code] : null,
                'contact' => $inquiry->contact ? ['id' => $inquiry->contact->id, 'name' => $inquiry->contact->displayName()] : null,
                'quotation' => $inquiry->quotation?->only(['id', 'ulid', 'number', 'status']),
                'items' => $inquiry->items->map(fn ($item) => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'part_number' => $item->part_number,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'note' => $item->note,
                    'part' => $item->part ? [
                        'id' => $item->part->id,
                        'part_number' => $item->part->part_number,
                        'list_price' => $item->part->list_price,
                        'stock_qty' => $item->part->stock_qty,
                        'uom' => $item->part->uom->code,
                        'discontinued' => $item->part->trashed(),
                    ] : null,
                ]),
            ],
            'activities' => $inquiry->activities->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user' => $log->user?->name,
                'created_at' => $log->created_at,
            ]),
            'suggestedCustomerId' => $inquiry->customer_id ? null : $this->suggestCustomer($inquiry),
            'salesReps' => User::orderBy('name')->get(['id', 'name', 'role']),
            'customers' => Customer::with('contacts:id,customer_id,salutation,name')->orderBy('name')->get(['id', 'code', 'name'])
                ->map(fn (Customer $customer) => [
                    'id' => $customer->id,
                    'code' => $customer->code,
                    'name' => $customer->name,
                    'contacts' => $customer->contacts->map(fn ($contact) => ['id' => $contact->id, 'name' => $contact->displayName()]),
                ]),
            'can' => [
                'triage' => Gate::allows('triage', $inquiry),
                'work' => Gate::allows('work', $inquiry),
            ],
        ]);
    }

    public function assign(Request $request, Inquiry $inquiry): RedirectResponse
    {
        Gate::authorize('triage', $inquiry);

        $data = $request->validate(['user_id' => ['required', Rule::exists('users', 'id')]]);

        $this->workflow->assign($inquiry, User::findOrFail($data['user_id']), $request->user());

        return $this->done('Inquiry assigned and moved to review.');
    }

    public function linkCustomer(Request $request, Inquiry $inquiry): RedirectResponse
    {
        Gate::authorize('work', $inquiry);

        $data = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'contact_id' => ['nullable', Rule::exists('contacts', 'id')->where('customer_id', $request->integer('customer_id'))->whereNull('deleted_at')],
        ]);

        $this->workflow->linkCustomer(
            $inquiry,
            Customer::findOrFail($data['customer_id']),
            isset($data['contact_id']) ? Contact::findOrFail($data['contact_id']) : null,
            $request->user(),
        );

        return $this->done('Customer linked.');
    }

    public function approve(Request $request, Inquiry $inquiry): RedirectResponse
    {
        Gate::authorize('triage', $inquiry);

        $this->workflow->approve($inquiry, $request->user());

        return $this->done('Inquiry approved.');
    }

    public function reject(Request $request, Inquiry $inquiry): RedirectResponse
    {
        Gate::authorize('triage', $inquiry);

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $this->workflow->reject($inquiry, $data['reason'], $request->user());

        return $this->done('Inquiry rejected.');
    }

    public function convert(Request $request, Inquiry $inquiry): RedirectResponse
    {
        Gate::authorize('work', $inquiry);

        $quotation = $this->workflow->convert($inquiry, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Quotation {$quotation->number} drafted from the inquiry. Review the prices, then save to send it."]);

        return redirect()->route('quotations.show', $quotation);
    }

    /** A known customer whose contacts share the inquirer's email domain. */
    private function suggestCustomer(Inquiry $inquiry): ?int
    {
        $domain = str($inquiry->email)->after('@')->lower()->toString();

        if ($domain === '' || in_array($domain, ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com'], true)) {
            return null;
        }

        return Contact::where('email', 'like', "%@{$domain}")->value('customer_id');
    }

    private function done(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
