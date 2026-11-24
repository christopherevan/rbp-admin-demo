<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use App\Models\Concerns\HasActivityLog;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property InquiryStatus $status
 * @property int|null $assigned_to
 */
#[Fillable(['name', 'company', 'email', 'phone', 'city', 'message', 'source'])]
class Inquiry extends Model
{
    use HasActivityLog, HasPublicUlid;

    /** @return HasMany<InquiryItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InquiryItem::class)->orderBy('id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return BelongsTo<User, $this> */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    /** @return BelongsTo<Quotation, $this> */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    protected function casts(): array
    {
        return [
            'status' => InquiryStatus::class,
            'decided_at' => 'datetime',
        ];
    }
}
