<?php

namespace App\Models;

use App\Enums\DescriptionStatus;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A product whose AI-written description is waiting for, or has had, a
 * human review.
 *
 * @property DescriptionStatus $ai_status
 * @property bool $product_type_locked
 * @property array<string, list<string>> $facts
 * @property array{errors: list<string>, warnings: list<string>} $ai_findings
 * @property array<string, mixed> $ai_meta
 */
#[Fillable([
    'supplier', 'title', 'category', 'product_type_id', 'product_type_locked',
    'family', 'facts', 'manufacturer_text', 'description_en', 'description_id',
    'meta_en', 'ai_status', 'ai_findings', 'ai_meta',
])]
class CatalogProduct extends Model
{
    use HasPublicUlid;

    /** @return BelongsTo<User, $this> */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** @return BelongsTo<CatalogProductType, $this> */
    public function productType(): BelongsTo
    {
        return $this->belongsTo(CatalogProductType::class, 'product_type_id');
    }

    protected function casts(): array
    {
        return [
            'ai_status' => DescriptionStatus::class,
            'product_type_locked' => 'boolean',
            'facts' => 'array',
            'ai_findings' => 'array',
            'ai_meta' => 'array',
            'decided_at' => 'datetime',
        ];
    }
}
