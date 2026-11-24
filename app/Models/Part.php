<?php

namespace App\Models;

use App\Models\Concerns\HasActivityLog;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['part_number', 'description', 'uom_id', 'list_price', 'stocked', 'stock_qty'])]
class Part extends Model
{
    use HasActivityLog, HasPublicUlid, SoftDeletes;

    /** @return BelongsTo<Uom, $this> */
    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    /** @return HasMany<QuotationLine, $this> */
    public function quotationLines(): HasMany
    {
        return $this->hasMany(QuotationLine::class);
    }

    protected function casts(): array
    {
        return [
            'list_price' => 'integer',
            'stocked' => 'boolean',
            'stock_qty' => 'integer',
        ];
    }
}
