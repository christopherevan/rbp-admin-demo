<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogAttributeGroup;
use App\Models\CatalogCategory;
use App\Models\CatalogCategoryTypeMap;
use App\Models\CatalogProduct;
use App\Models\CatalogProductType;
use App\Models\CatalogProductTypeKeyAttribute;
use App\Services\CatalogTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Catalog > Product types: unify supplier categories under shared customer
 * types, configure key specs per type, and apply mappings onto products.
 */
class CatalogTypeController extends Controller
{
    public function __construct(private CatalogTypeService $types) {}

    public function index(): Response
    {
        $types = CatalogProductType::query()
            ->withCount(['products', 'categoryMappings', 'keyAttributes'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $unmapped = CatalogProduct::query()
            ->whereNull('product_type_id')
            ->count();

        return Inertia::render('catalog/types/index', [
            'types' => $types->map(fn (CatalogProductType $type): array => [
                'id' => $type->id,
                'ulid' => $type->ulid,
                'name' => $type->name,
                'slug' => $type->slug,
                'header_image' => $type->header_image,
                'sort_order' => $type->sort_order,
                'products_count' => (int) ($type->products_count ?? 0),
                'category_mappings_count' => (int) ($type->category_mappings_count ?? 0),
                'key_attributes_count' => (int) ($type->key_attributes_count ?? 0),
            ]),
            'unmapped' => $unmapped,
            'pending' => $this->types->pendingChanges(),
            'canManage' => Gate::allows('manage-catalog'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-catalog');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $type = CatalogProductType::create([
            'name' => $data['name'],
            'slug' => CatalogProductType::slugFor($data['name']),
            'sort_order' => (int) CatalogProductType::query()->max('sort_order') + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Type “{$type->name}” created."]);

        return back();
    }

    public function show(CatalogProductType $type): Response
    {
        $type->load(['keyAttributes.attributeGroups']);

        $categories = DB::table('catalog_categories as c')
            ->leftJoin('catalog_category_type_map as m', function ($join): void {
                $join->on('m.supplier', '=', 'c.supplier')
                    ->on('m.category_path', '=', 'c.full_path');
            })
            ->leftJoin('catalog_product_types as t', 't.id', '=', 'm.product_type_id')
            ->leftJoin('catalog_products as p', function ($join): void {
                $join->on('p.supplier', '=', 'c.supplier')
                    ->on('p.category', '=', 'c.full_path');
            })
            ->groupBy('c.id', 'c.supplier', 'c.full_path', 'm.product_type_id', 't.name')
            ->orderBy('c.supplier')
            ->orderBy('c.full_path')
            ->get([
                'c.id',
                'c.supplier',
                'c.full_path',
                'm.product_type_id',
                't.name as assigned_type',
                DB::raw('COUNT(p.id) as product_count'),
            ])
            ->groupBy('supplier')
            ->map(fn ($rows) => $rows->map(fn ($row): array => [
                'id' => (int) $row->id,
                'supplier' => (string) $row->supplier,
                'full_path' => (string) $row->full_path,
                'product_type_id' => $row->product_type_id !== null ? (int) $row->product_type_id : null,
                'assigned_type' => $row->assigned_type !== null ? (string) $row->assigned_type : null,
                'product_count' => (int) $row->product_count,
            ])->values()->all())
            ->all();

        $groups = CatalogAttributeGroup::query()
            ->orderBy('supplier')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->groupBy('supplier')
            ->map(fn ($rows) => $rows->map(fn (CatalogAttributeGroup $group): array => [
                'id' => $group->id,
                'supplier' => $group->supplier,
                'label' => $group->label,
                'unit' => $group->unit,
            ])->values()->all())
            ->all();

        $claimed = DB::table('catalog_key_attribute_group_map as m')
            ->join('catalog_product_type_key_attributes as k', 'k.id', '=', 'm.key_attribute_id')
            ->where('k.product_type_id', $type->id)
            ->pluck('k.label', 'm.attribute_group_id')
            ->mapWithKeys(fn ($label, $groupId): array => [(int) $groupId => (string) $label])
            ->all();

        return Inertia::render('catalog/types/show', [
            'type' => [
                'id' => $type->id,
                'ulid' => $type->ulid,
                'name' => $type->name,
                'slug' => $type->slug,
                'header_image' => $type->header_image,
                'sort_order' => $type->sort_order,
                'key_attributes' => $type->keyAttributes->map(fn (CatalogProductTypeKeyAttribute $attr): array => [
                    'id' => $attr->id,
                    'ulid' => $attr->ulid,
                    'label' => $attr->label,
                    'unit' => $attr->unit,
                    'position' => $attr->position,
                    'group_ids' => $attr->attributeGroups->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
                ])->values()->all(),
            ],
            'headerPresets' => CatalogProductType::HEADER_PRESETS,
            'defaultHeader' => CatalogProductType::DEFAULT_HEADER,
            'categories' => $categories,
            'groups' => $groups,
            'claimed' => $claimed,
            'pending' => $this->types->pendingChanges(),
            'canManage' => Gate::allows('manage-catalog'),
        ]);
    }

    public function update(Request $request, CatalogProductType $type): RedirectResponse
    {
        Gate::authorize('manage-catalog');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'nullable', 'string', 'max:120', 'alpha_dash',
                Rule::unique('catalog_product_types', 'slug')->ignore($type->id),
                Rule::notIn(CatalogProductType::RESERVED_SLUGS),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'header_image' => ['nullable', Rule::in(CatalogProductType::HEADER_PRESETS)],
        ]);

        $type->name = $data['name'];

        // Renaming leaves the slug untouched so indexed URLs never drift;
        // only an explicit edit or clearing the slug updates it.
        if ($request->filled('slug')) {
            $type->slug = (string) $data['slug'];
        } elseif ($request->has('slug')) {
            $type->slug = CatalogProductType::slugFor($data['name'], $type->id);
        }

        $type->sort_order = (int) ($data['sort_order'] ?? $type->sort_order);

        if ($request->has('header_image')) {
            $type->header_image = $data['header_image'] ?: null;
        }

        $type->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Type updated.']);

        return back();
    }

    public function destroy(CatalogProductType $type): RedirectResponse
    {
        Gate::authorize('manage-catalog');

        $name = $type->name;
        $type->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Type “{$name}” deleted. Run Apply to refresh product assignments.",
        ]);

        return redirect()->route('catalog.types.index');
    }

    public function syncCategories(Request $request, CatalogProductType $type): RedirectResponse
    {
        Gate::authorize('manage-catalog');

        $data = $request->validate([
            'categories' => ['array'],
            'categories.*' => ['integer'],
        ]);

        $chosen = CatalogCategory::query()
            ->whereIn('id', $data['categories'] ?? [])
            ->get(['supplier', 'full_path']);

        DB::transaction(function () use ($type, $chosen): void {
            $type->categoryMappings()->delete();

            foreach ($chosen as $category) {
                CatalogCategoryTypeMap::query()->updateOrCreate(
                    ['supplier' => $category->supplier, 'category_path' => $category->full_path],
                    ['product_type_id' => $type->id],
                );
            }
        });

        $count = $chosen->count();
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $count.' '.str('category')->plural($count).' assigned. Run Apply to move the products.',
        ]);

        return back();
    }

    public function storeKeyAttribute(Request $request, CatalogProductType $type): RedirectResponse
    {
        Gate::authorize('manage-catalog');

        $data = $request->validate([
            'label' => [
                'required', 'string', 'max:80',
                Rule::unique('catalog_product_type_key_attributes', 'label')
                    ->where('product_type_id', $type->id),
            ],
            'unit' => ['nullable', 'string', 'max:24'],
        ]);

        $type->keyAttributes()->create([
            'label' => $data['label'],
            'unit' => $data['unit'] ?: null,
            'position' => (int) $type->keyAttributes()->max('position') + 1,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Key attribute “{$data['label']}” added. Tick the supplier fields that feed it.",
        ]);

        return back();
    }

    public function updateKeyAttribute(Request $request, CatalogProductTypeKeyAttribute $keyAttribute): RedirectResponse
    {
        Gate::authorize('manage-catalog');

        $data = $request->validate([
            'label' => [
                'required', 'string', 'max:80',
                Rule::unique('catalog_product_type_key_attributes', 'label')
                    ->where('product_type_id', $keyAttribute->product_type_id)
                    ->ignore($keyAttribute->id),
            ],
            'unit' => ['nullable', 'string', 'max:24'],
            'position' => ['nullable', 'integer', 'min:0'],
            'groups' => ['array'],
            'groups.*' => ['integer', 'exists:catalog_attribute_groups,id'],
        ]);

        $conflict = DB::table('catalog_key_attribute_group_map as m')
            ->join('catalog_product_type_key_attributes as k', 'k.id', '=', 'm.key_attribute_id')
            ->where('k.product_type_id', $keyAttribute->product_type_id)
            ->where('k.id', '!=', $keyAttribute->id)
            ->whereIn('m.attribute_group_id', $data['groups'] ?? [])
            ->value('k.label');

        if ($conflict) {
            return back()->withErrors([
                'groups' => "One of those fields already feeds “{$conflict}”. Remove it there first.",
            ]);
        }

        DB::transaction(function () use ($keyAttribute, $data): void {
            $keyAttribute->update([
                'label' => $data['label'],
                'unit' => $data['unit'] ?: null,
                'position' => (int) ($data['position'] ?? $keyAttribute->position),
            ]);

            $keyAttribute->attributeGroups()->sync($data['groups'] ?? []);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "“{$data['label']}” updated."]);

        return back();
    }

    public function destroyKeyAttribute(CatalogProductTypeKeyAttribute $keyAttribute): RedirectResponse
    {
        Gate::authorize('manage-catalog');

        $label = $keyAttribute->label;
        $keyAttribute->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Key attribute “{$label}” removed."]);

        return back();
    }

    public function apply(): RedirectResponse
    {
        Gate::authorize('manage-catalog');

        $result = $this->types->apply();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => sprintf('Applied: %d assigned or changed, %d cleared.', $result['set'], $result['clear']),
        ]);

        return back();
    }
}
