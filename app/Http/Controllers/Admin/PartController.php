<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Models\Uom;
use App\Support\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PartController extends Controller
{
    public function index(Request $request): Response
    {
        $discontinued = $request->boolean('discontinued');

        $query = Part::query()
            ->with('uom:id,code')
            ->when($discontinued, fn ($q) => $q->onlyTrashed())
            ->when($request->query('stock') === 'low', fn ($q) => $q->where('stocked', true)->where('stock_qty', '<', 10));

        $parts = TableQuery::paginate($query, $request, ['part_number', 'description'], ['part_number', 'list_price', 'stock_qty', 'updated_at', 'created_at'], 'part_number')
            ->through(fn (Part $part) => [
                'id' => $part->id,
                'ulid' => $part->ulid,
                'part_number' => $part->part_number,
                'description' => $part->description,
                'uom_id' => $part->uom_id,
                'uom' => $part->uom->code,
                'list_price' => $part->list_price,
                'stocked' => $part->stocked,
                'stock_qty' => $part->stock_qty,
                'updated_at' => $part->updated_at,
                'discontinued' => $part->trashed(),
            ]);

        return Inertia::render('parts/index', [
            'parts' => $parts,
            'filters' => [...$request->only(['q', 'sort', 'per_page', 'stock']), 'discontinued' => $discontinued],
            'uoms' => Uom::orderBy('code')->get(['id', 'code', 'name']),
            'lowStockCount' => Part::where('stocked', true)->where('stock_qty', '<', 10)->count(),
            'discontinuedCount' => Part::onlyTrashed()->count(),
        ]);
    }

    /** Typeahead for the quotation line editor. */
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        $parts = Part::query()
            ->with('uom:id,code')
            ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('part_number', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")))
            ->orderBy('part_number')
            ->limit(20)
            ->get()
            ->map(fn (Part $part) => [
                'id' => $part->id,
                'part_number' => $part->part_number,
                'description' => $part->description,
                'uom' => $part->uom->code,
                'list_price' => $part->list_price,
                'stocked' => $part->stocked,
                'stock_qty' => $part->stock_qty,
            ]);

        return response()->json($parts);
    }

    public function store(Request $request): RedirectResponse
    {
        $part = Part::create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Part {$part->part_number} added."]);

        return back();
    }

    public function update(Request $request, Part $part): RedirectResponse
    {
        $part->update(collect($this->validated($request, $part))->except('stock_qty')->all());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Part {$part->part_number} saved."]);

        return back();
    }

    /**
     * Discontinue a part. It's soft-deleted, so quotations that already use
     * it still show it.
     */
    public function destroy(Part $part): RedirectResponse
    {
        Gate::authorize('archive-records');

        $part->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Part {$part->part_number} discontinued."]);

        return back();
    }

    public function restore(Part $part): RedirectResponse
    {
        Gate::authorize('archive-records');

        $part->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Part {$part->part_number} restored."]);

        return back();
    }

    public function adjustStock(Request $request, Part $part): RedirectResponse
    {
        if (! $part->stocked) {
            throw new WorkflowException("{$part->part_number} is a service and isn't stocked.");
        }

        $data = $request->validate([
            'delta' => ['required', 'integer', 'not_in:0', 'between:-100000,100000'],
            'reason' => ['required', 'string', 'max:120'],
        ]);

        $part->increment('stock_qty', $data['delta']);
        $part->log('stock_adjusted', sprintf('Stock %+d (%s), now %d.', $data['delta'], $data['reason'], $part->stock_qty), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Stock for {$part->part_number} is now {$part->stock_qty}."]);

        return back();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Part $part = null): array
    {
        $request->merge(['part_number' => strtoupper(trim((string) $request->input('part_number')))]);

        return $request->validate([
            'part_number' => ['required', 'max:40', 'regex:/^[A-Z0-9][A-Z0-9.\-\/]*$/', Rule::unique('parts', 'part_number')->ignore($part)],
            'description' => ['required', 'string', 'max:200'],
            'uom_id' => ['required', Rule::exists('uoms', 'id')],
            'list_price' => ['required', 'integer', 'min:0', 'max:1000000000000'],
            'stocked' => ['required', 'boolean'],
            'stock_qty' => ['sometimes', 'integer', 'between:0,1000000'],
        ]);
    }
}
