<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['part_id', 'title', 'part_number', 'quantity', 'unit', 'note'])]
class InquiryItem extends Model
{
    public $timestamps = false;

    /** @return BelongsTo<Part, $this> */
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class)->withTrashed();
    }
}
