<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['salutation', 'name', 'phone', 'email'])]
class Contact extends Model
{
    use SoftDeletes;

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function displayName(): string
    {
        return ($this->salutation === 'ms' ? 'Ms. ' : 'Mr. ').$this->name;
    }
}
