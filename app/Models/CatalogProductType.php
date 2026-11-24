<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Product taxonomy unifying supplier category trees (e.g. Beltora and
 * Conveyix) into customer-facing product types and spec sheets.
 *
 * @property int $sort_order
 */
#[Fillable(['name', 'slug', 'header_image', 'sort_order'])]
class CatalogProductType extends Model
{
    use HasPublicUlid;

    /** @var list<string> */
    public const RESERVED_SLUGS = [
        'catalog', 'search', 'locale', 'p', 'products', 'quote', 'web-home',
        'storage', 'en', 'id',
    ];

    public const DEFAULT_HEADER = 'products-conveyor-default.avif';

    /** @var list<string> */
    public const HEADER_PRESETS = [
        'belts-modular.avif',
        'chains-tabletop.avif',
        'frame-components.avif',
        'bearings-rollers.avif',
    ];

    /** @return HasMany<CatalogProduct, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(CatalogProduct::class, 'product_type_id');
    }

    /** @return HasMany<CatalogCategoryTypeMap, $this> */
    public function categoryMappings(): HasMany
    {
        return $this->hasMany(CatalogCategoryTypeMap::class, 'product_type_id');
    }

    /** @return HasMany<CatalogProductTypeKeyAttribute, $this> */
    public function keyAttributes(): HasMany
    {
        return $this->hasMany(CatalogProductTypeKeyAttribute::class, 'product_type_id')
            ->orderBy('position')
            ->orderBy('id');
    }

    /**
     * Generate a unique slug for `$name`. Only called on creation or when an
     * editor deliberately clears the slug field so renaming never silently
     * moves indexed public URLs.
     */
    public static function slugFor(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'type';
        $slug = $base;
        $n = 1;

        while (self::slugTaken($slug, $ignoreId)) {
            $slug = $base.'-'.(++$n);
        }

        return $slug;
    }

    private static function slugTaken(string $slug, ?int $ignoreId): bool
    {
        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            return true;
        }

        return self::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
