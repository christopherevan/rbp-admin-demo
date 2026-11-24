<?php

use App\Enums\DescriptionStatus;
use App\Models\CatalogAttributeGroup;
use App\Models\CatalogCategory;
use App\Models\CatalogCategoryTypeMap;
use App\Models\CatalogProduct;
use App\Models\CatalogProductType;
use App\Models\CatalogProductTypeKeyAttribute;
use App\Services\CatalogTypeService;
use Inertia\Testing\AssertableInertia as Assert;

it('manages product type CRUD, slug preservation, and reserved slugs for admins', function () {
    $admin = admin();

    $this->actingAs($admin)
        ->post(route('catalog.types.store'), ['name' => 'Wear Strips'])
        ->assertRedirect();

    $type = CatalogProductType::where('name', 'Wear Strips')->firstOrFail();
    expect($type->slug)->toBe('wear-strips');

    // Renaming alone preserves the existing slug.
    $this->actingAs($admin)
        ->put(route('catalog.types.update', $type), [
            'name' => 'UHMW Wear Strips',
            'slug' => $type->slug,
            'sort_order' => 4,
            'header_image' => 'belts-modular.avif',
        ])
        ->assertSessionHasNoErrors();

    expect($type->fresh()->name)->toBe('UHMW Wear Strips')
        ->and($type->fresh()->slug)->toBe('wear-strips')
        ->and($type->fresh()->header_image)->toBe('belts-modular.avif');

    // Clearing the slug regenerates it from the new name.
    $this->actingAs($admin)
        ->put(route('catalog.types.update', $type), [
            'name' => 'UHMW Wear Strips',
            'slug' => '',
        ])
        ->assertSessionHasNoErrors();

    expect($type->fresh()->slug)->toBe('uhmw-wear-strips');

    // Reserved slugs are rejected.
    $this->actingAs($admin)
        ->put(route('catalog.types.update', $type), [
            'name' => 'Catalog',
            'slug' => 'catalog',
        ])
        ->assertSessionHasErrors('slug');

    $this->actingAs($admin)
        ->delete(route('catalog.types.destroy', $type))
        ->assertRedirect(route('catalog.types.index'));

    expect(CatalogProductType::find($type->id))->toBeNull();
});

it('syncs categories, detects drift, applies to unlocked products only', function () {
    $admin = admin();

    $belts = CatalogProductType::create(['name' => 'Belts', 'slug' => 'belts', 'sort_order' => 1]);
    $chains = CatalogProductType::create(['name' => 'Chains', 'slug' => 'chains', 'sort_order' => 2]);

    $catBelts = CatalogCategory::create(['supplier' => 'Beltora', 'full_path' => 'Modular Belts › Flat Top']);
    $catChains = CatalogCategory::create(['supplier' => 'Beltora', 'full_path' => 'Chains › Table Top']);

    CatalogCategoryTypeMap::create([
        'supplier' => 'Beltora',
        'category_path' => $catChains->full_path,
        'product_type_id' => $chains->id,
    ]);

    $unlocked = CatalogProduct::create([
        'supplier' => 'Beltora',
        'title' => 'Unlocked Belt',
        'category' => $catBelts->full_path,
        'family' => 'belt',
        'facts' => [],
        'manufacturer_text' => 'x',
        'description_en' => 'x',
        'description_id' => 'x',
        'meta_en' => 'x',
        'ai_status' => DescriptionStatus::Approved,
        'ai_findings' => ['errors' => [], 'warnings' => []],
        'ai_meta' => [],
    ]);

    $locked = CatalogProduct::create([
        'supplier' => 'Beltora',
        'title' => 'Locked Belt',
        'category' => $catBelts->full_path,
        'product_type_id' => $chains->id,
        'product_type_locked' => true,
        'family' => 'belt',
        'facts' => [],
        'manufacturer_text' => 'x',
        'description_en' => 'x',
        'description_id' => 'x',
        'meta_en' => 'x',
        'ai_status' => DescriptionStatus::Approved,
        'ai_findings' => ['errors' => [], 'warnings' => []],
        'ai_meta' => [],
    ]);

    // Assign both categories to $belts (moving $catChains away from $chains).
    $this->actingAs($admin)
        ->post(route('catalog.types.categories', $belts), [
            'categories' => [$catBelts->id, $catChains->id],
        ])
        ->assertRedirect();

    expect(CatalogCategoryTypeMap::where('category_path', $catChains->full_path)->value('product_type_id'))
        ->toBe($belts->id)
        ->and(app(CatalogTypeService::class)->pendingChanges())->toBe(['set' => 1, 'clear' => 0])
        ->and($unlocked->fresh()->product_type_id)->toBeNull();

    // Apply moves unlocked product and preserves locked product.
    $this->actingAs($admin)
        ->post(route('catalog.types.apply'))
        ->assertRedirect();

    expect($unlocked->fresh()->product_type_id)->toBe($belts->id)
        ->and($locked->fresh()->product_type_id)->toBe($chains->id)
        ->and(app(CatalogTypeService::class)->pendingChanges())->toBe(['set' => 0, 'clear' => 0]);
});

it('manages key attributes and rejects conflicting attribute group mappings', function () {
    $admin = admin();

    $type = CatalogProductType::create(['name' => 'Modular Belts', 'slug' => 'modular-belts', 'sort_order' => 1]);
    $group = CatalogAttributeGroup::create(['supplier' => 'Beltora', 'label' => 'Pitch', 'unit' => 'mm', 'position' => 1]);

    $this->actingAs($admin)
        ->post(route('catalog.types.attributes.store', $type), ['label' => 'Pitch', 'unit' => 'mm'])
        ->assertRedirect();

    $this->actingAs($admin)
        ->post(route('catalog.types.attributes.store', $type), ['label' => 'Spacing', 'unit' => 'mm'])
        ->assertRedirect();

    $first = CatalogProductTypeKeyAttribute::where('label', 'Pitch')->firstOrFail();
    $second = CatalogProductTypeKeyAttribute::where('label', 'Spacing')->firstOrFail();

    $this->actingAs($admin)
        ->put(route('catalog.types.attributes.update', $first), [
            'label' => 'Pitch',
            'unit' => 'mm',
            'position' => 1,
            'groups' => [$group->id],
        ])
        ->assertSessionHasNoErrors();

    // Mapping the same group to a second attribute on the same type fails validation.
    $this->actingAs($admin)
        ->put(route('catalog.types.attributes.update', $second), [
            'label' => 'Spacing',
            'unit' => 'mm',
            'position' => 2,
            'groups' => [$group->id],
        ])
        ->assertSessionHasErrors('groups');

    expect($second->fresh()->attributeGroups()->count())->toBe(0);

    $this->actingAs($admin)
        ->delete(route('catalog.types.attributes.destroy', $second))
        ->assertRedirect();

    expect(CatalogProductTypeKeyAttribute::find($second->id))->toBeNull();
});

it('allows sales to view product types read-only while blocking all mutations', function () {
    $sales = salesRep();
    $type = CatalogProductType::create(['name' => 'Belts', 'slug' => 'belts', 'sort_order' => 1]);
    $attr = $type->keyAttributes()->create(['label' => 'Pitch', 'unit' => 'mm', 'position' => 1]);

    $this->actingAs($sales)->get(route('catalog.types.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('canManage', false)->etc());

    $this->actingAs($sales)->get(route('catalog.types.show', $type))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('canManage', false)->etc());

    $this->actingAs($sales)->post(route('catalog.types.store'), ['name' => 'Chains']);
    $this->actingAs($sales)->put(route('catalog.types.update', $type), ['name' => 'Renamed']);
    $this->actingAs($sales)->delete(route('catalog.types.destroy', $type));
    $this->actingAs($sales)->post(route('catalog.types.categories', $type), ['categories' => []]);
    $this->actingAs($sales)->post(route('catalog.types.attributes.store', $type), ['label' => 'Width']);
    $this->actingAs($sales)->put(route('catalog.types.attributes.update', $attr), ['label' => 'Changed']);
    $this->actingAs($sales)->delete(route('catalog.types.attributes.destroy', $attr));
    $this->actingAs($sales)->post(route('catalog.types.apply'));

    expect(CatalogProductType::count())->toBe(1)
        ->and($type->fresh()->name)->toBe('Belts')
        ->and($attr->fresh()->label)->toBe('Pitch');
});
