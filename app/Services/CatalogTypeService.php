<?php

namespace App\Services;

use App\Models\CatalogCategoryTypeMap;
use App\Models\CatalogProduct;
use Illuminate\Support\Facades\DB;

/**
 * Resolves supplier category-to-type mappings onto unlocked catalog products.
 */
class CatalogTypeService
{
    /**
     * How far catalog products have drifted from `catalog_category_type_map`.
     *
     * @return array{set: int, clear: int}
     */
    public function pendingChanges(): array
    {
        $set = DB::table('catalog_products')
            ->join('catalog_category_type_map as m', function ($join): void {
                $join->on('m.supplier', '=', 'catalog_products.supplier')
                    ->on('m.category_path', '=', 'catalog_products.category');
            })
            ->where('catalog_products.product_type_locked', false)
            ->where(function ($q): void {
                $q->whereNull('catalog_products.product_type_id')
                    ->orWhereColumn('catalog_products.product_type_id', '!=', 'm.product_type_id');
            })
            ->count();

        $clear = DB::table('catalog_products')
            ->where('product_type_locked', false)
            ->whereNotNull('product_type_id')
            ->whereNotExists(function ($q): void {
                $q->select(DB::raw(1))
                    ->from('catalog_category_type_map as m')
                    ->whereColumn('m.supplier', 'catalog_products.supplier')
                    ->whereColumn('m.category_path', 'catalog_products.category');
            })
            ->count();

        return ['set' => $set, 'clear' => $clear];
    }

    /**
     * Apply the category-to-type map onto all unlocked products.
     *
     * @return array{set: int, clear: int, locked: int}
     */
    public function apply(): array
    {
        $before = $this->pendingChanges();
        $locked = CatalogProduct::query()->where('product_type_locked', true)->count();

        DB::transaction(function (): void {
            $now = now();

            // Clear unmapped unlocked products first so a product moving types
            // never holds stale assignments.
            DB::table('catalog_products')
                ->where('product_type_locked', false)
                ->whereNotNull('product_type_id')
                ->whereNotExists(function ($q): void {
                    $q->select(DB::raw(1))
                        ->from('catalog_category_type_map as m')
                        ->whereColumn('m.supplier', 'catalog_products.supplier')
                        ->whereColumn('m.category_path', 'catalog_products.category');
                })
                ->update(['product_type_id' => null, 'updated_at' => $now]);

            // Loop per mapping instead of UPDATE ... JOIN for SQLite compatibility.
            $mappings = CatalogCategoryTypeMap::query()->get(['supplier', 'category_path', 'product_type_id']);

            foreach ($mappings as $mapping) {
                CatalogProduct::query()
                    ->where('product_type_locked', false)
                    ->where('supplier', $mapping->supplier)
                    ->where('category', $mapping->category_path)
                    ->update([
                        'product_type_id' => $mapping->product_type_id,
                        'updated_at' => $now,
                    ]);
            }
        });

        return [
            'set' => $before['set'],
            'clear' => $before['clear'],
            'locked' => $locked,
        ];
    }
}
