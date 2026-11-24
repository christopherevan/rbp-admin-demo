<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DescriptionStatus;
use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Models\CatalogProduct;
use App\Services\DescriptionGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The review queue for AI-written product descriptions: every draft is
 * fact-checked by DescriptionGuard, then approved, edited or rejected by a
 * person before it can be published.
 */
class CatalogDescriptionController extends Controller
{
    public function __construct(private DescriptionGuard $guard) {}

    public function index(Request $request): Response
    {
        $status = DescriptionStatus::tryFrom((string) $request->query('status', 'pending')) ?? DescriptionStatus::Pending;

        $products = CatalogProduct::query()
            ->where('ai_status', $status)
            ->when($request->query('q'), fn ($q, $term) => $q->where('title', 'like', '%'.str($term)->limit(100, '').'%'))
            ->orderBy('id')
            ->get();

        $selected = $products->firstWhere('ulid', $request->query('selected')) ?? $products->first();
        $selected?->load(['decider:id,name', 'productType:id,ulid,name,slug']);

        return Inertia::render('catalog/descriptions', [
            'filters' => ['status' => $status->value, 'q' => $request->query('q')],
            'counts' => CatalogProduct::query()->selectRaw('ai_status, count(*) as total')->groupBy('ai_status')->pluck('total', 'ai_status'),
            'products' => $products->map(fn (CatalogProduct $product) => [
                'id' => $product->id,
                'ulid' => $product->ulid,
                'title' => $product->title,
                'supplier' => $product->supplier,
                'category' => $product->category,
                'errors' => count($product->ai_findings['errors'] ?? []),
                'warnings' => count($product->ai_findings['warnings'] ?? []),
            ]),
            'selected' => $selected ? [
                ...$selected->only(['id', 'ulid', 'title', 'supplier', 'category', 'family', 'facts', 'manufacturer_text', 'description_en', 'description_id', 'meta_en', 'ai_status', 'ai_findings', 'ai_meta', 'rejection_note', 'decided_at']),
                'decider' => $selected->decider?->name,
                'product_type' => $selected->productType ? $selected->productType->only(['ulid', 'name', 'slug']) : null,
            ] : null,
            'similarityLimit' => DescriptionGuard::SIMILARITY_LIMIT,
            'canDecide' => Gate::allows('decide-descriptions'),
        ]);
    }

    /** Save a reviewer's edits and run the guard again on the new text. */
    public function update(Request $request, CatalogProduct $product): RedirectResponse
    {
        $this->ensurePending($product);

        $data = $request->validate([
            'description_en' => ['required', 'string', 'max:3000'],
            'description_id' => ['required', 'string', 'max:3000'],
            'meta_en' => ['required', 'string', 'max:200'],
        ]);

        $product->fill($data);
        $this->recheck($product);
        $product->save();

        $errors = count($product->ai_findings['errors']);

        Inertia::flash('toast', [
            'type' => $errors ? 'warning' : 'success',
            'message' => $errors ? "Saved. The guard still reports {$errors} ".str('error')->plural($errors).'.' : 'Saved. The guard found no errors.',
        ]);

        return back();
    }

    public function decide(Request $request, CatalogProduct $product): RedirectResponse
    {
        Gate::authorize('decide-descriptions');
        $this->ensurePending($product);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'note' => ['nullable', 'string', 'max:300'],
        ]);

        if ($data['decision'] === 'approve' && ! empty($product->ai_findings['errors'])) {
            throw new WorkflowException('Fix the guard\'s errors before approving this description.');
        }

        $product->ai_status = $data['decision'] === 'approve' ? DescriptionStatus::Approved : DescriptionStatus::Rejected;
        $product->rejection_note = $data['decision'] === 'reject' ? ($data['note'] ?? null) : null;
        $product->decided_by = $request->user()->id;
        $product->decided_at = now();
        $product->save();

        $next = CatalogProduct::where('ai_status', DescriptionStatus::Pending)->where('id', '>', $product->id)->orderBy('id')->value('ulid')
            ?? CatalogProduct::where('ai_status', DescriptionStatus::Pending)->orderBy('id')->value('ulid');

        Inertia::flash('toast', ['type' => 'success', 'message' => "“{$product->title}” ".($data['decision'] === 'approve' ? 'approved.' : 'rejected.')]);

        return redirect()->route('catalog.descriptions.index', array_filter(['selected' => $next]));
    }

    public function bulkApprove(Request $request): RedirectResponse
    {
        Gate::authorize('decide-descriptions');

        $data = $request->validate(['ids' => ['required', 'array', 'max:100'], 'ids.*' => ['integer']]);

        $products = CatalogProduct::whereIn('id', $data['ids'])->where('ai_status', DescriptionStatus::Pending)->get();
        $clean = $products->filter(fn (CatalogProduct $product) => empty($product->ai_findings['errors']));

        CatalogProduct::whereIn('id', $clean->pluck('id'))->update([
            'ai_status' => DescriptionStatus::Approved,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        $skipped = $products->count() - $clean->count();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Approved {$clean->count()}.".($skipped ? " Skipped {$skipped} with guard errors." : '')]);

        return redirect()->route('catalog.descriptions.index');
    }

    private function recheck(CatalogProduct $product): void
    {
        $result = $this->guard->check($product->description_en, $product->facts, $product->family, $product->title, $product->manufacturer_text);

        $product->ai_findings = ['errors' => $result['errors'], 'warnings' => $result['warnings']];
        $product->ai_meta = [...$product->ai_meta, 'similarity' => $result['similarity'], 'edited' => true];
    }

    private function ensurePending(CatalogProduct $product): void
    {
        if ($product->ai_status !== DescriptionStatus::Pending) {
            throw new WorkflowException('This description has already been reviewed.');
        }
    }
}
