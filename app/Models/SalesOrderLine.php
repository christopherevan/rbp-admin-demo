<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['quotation_line_id', 'part_id', 'quantity', 'unit_price', 'line_total'])]
class SalesOrderLine extends Model
{
    /** @return BelongsTo<Part, $this> */
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class)->withTrashed();
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'line_total' => 'integer',
        ];
    }
}
