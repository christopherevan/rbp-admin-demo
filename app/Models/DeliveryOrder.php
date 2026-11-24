<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['number', 'ship_to_name', 'ship_to_address', 'notes', 'status', 'created_by'])]
class DeliveryOrder extends Model
{
    public const ON_DELIVERY = 'on_delivery';

    public const COMPLETED = 'completed';

    /** @return BelongsTo<Quotation, $this> */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function isDelivered(): bool
    {
        return $this->status === self::COMPLETED;
    }

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
        ];
    }
}
