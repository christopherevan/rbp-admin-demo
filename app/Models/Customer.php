<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'name', 'address_line1', 'address_line2', 'channel', 'notes'])]
class Customer extends Model
{
    use HasPublicUlid, SoftDeletes;

    public const CHANNELS = ['direct', 'marketplace'];

    /** @return HasMany<Contact, $this> */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class)->orderBy('id');
    }

    /** @return HasMany<Quotation, $this> */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function address(): string
    {
        return collect([$this->address_line1, $this->address_line2])->filter()->implode(', ');
    }
}
