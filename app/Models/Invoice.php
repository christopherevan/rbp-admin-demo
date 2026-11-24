<?php

namespace App\Models;

use App\Enums\InvoiceType;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property InvoiceType $type
 * @property int $amount_due
 */
#[Fillable(['delivery_order_id', 'type', 'number', 'bill_to_name', 'bill_to_address', 'amount_due', 'status', 'created_by'])]
class Invoice extends Model
{
    use HasPublicUlid;

    public const PENDING = 'pending';

    public const PAID = 'paid';

    /** @return BelongsTo<Quotation, $this> */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    /** @return BelongsTo<DeliveryOrder, $this> */
    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('received_at')->orderBy('id');
    }

    public function amountPaid(): int
    {
        return (int) ($this->relationLoaded('payments')
            ? $this->payments->sum('amount')
            : $this->payments()->sum('amount'));
    }

    public function balance(): int
    {
        return max(0, $this->amount_due - $this->amountPaid());
    }

    public function isPaid(): bool
    {
        return $this->status === self::PAID;
    }

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'amount_due' => 'integer',
            'paid_at' => 'datetime',
        ];
    }
}
