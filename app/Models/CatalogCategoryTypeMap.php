<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['supplier', 'category_path', 'product_type_id'])]
class CatalogCategoryTypeMap extends Model
{
    protected $table = 'catalog_category_type_map';

    /** @return BelongsTo<CatalogProductType, $this> */
    public function productType(): BelongsTo
    {
        return $this->belongsTo(CatalogProductType::class, 'product_type_id');
    }
}
