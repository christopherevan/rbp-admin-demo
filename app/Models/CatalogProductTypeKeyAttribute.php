<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $position
 */
#[Fillable(['product_type_id', 'label', 'unit', 'position'])]
class CatalogProductTypeKeyAttribute extends Model
{
    use HasPublicUlid;

    /** @return BelongsTo<CatalogProductType, $this> */
    public function productType(): BelongsTo
    {
        return $this->belongsTo(CatalogProductType::class, 'product_type_id');
    }

    /** @return BelongsToMany<CatalogAttributeGroup, $this> */
    public function attributeGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            CatalogAttributeGroup::class,
            'catalog_key_attribute_group_map',
            'key_attribute_id',
            'attribute_group_id',
        );
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }
}
