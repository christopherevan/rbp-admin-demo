<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $position
 */
#[Fillable(['supplier', 'label', 'unit', 'position'])]
class CatalogAttributeGroup extends Model
{
    /** @return BelongsToMany<CatalogProductTypeKeyAttribute, $this> */
    public function keyAttributes(): BelongsToMany
    {
        return $this->belongsToMany(
            CatalogProductTypeKeyAttribute::class,
            'catalog_key_attribute_group_map',
            'attribute_group_id',
            'key_attribute_id',
        );
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }
}
