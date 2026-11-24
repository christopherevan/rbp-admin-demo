<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name'])]
class Uom extends Model
{
    use HasPublicUlid;

    /** @return HasMany<Part, $this> */
    public function parts(): HasMany
    {
        return $this->hasMany(Part::class);
    }
}
