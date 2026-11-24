<?php

namespace App\Models;

use App\Enums\InvoiceType;
use App\Enums\QuotationStatus;
use App\Models\Concerns\HasActivityLog;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $ulid
 * @property string $number
 * @property QuotationStatus $status
 * @property int $sales_user_id
 * @property string $discount_pct
 * @property int $ppn_rate
 * @property int $dp_amount
 * @property Carbon|null $customer_po_at
 */
#[Fillable([
    'customer_id', 'contact_id', 'sales_user_id', 'delivery_point', 'discount_pct', 'ppn_rate',
    'lead_time_value', 'lead_time_unit', 'validity_value', 'validity_unit', 'payment_method',
    'payment_terms_days', 'dp_amount', 'notes',
])]
class Quotation extends Model
{
    use HasActivityLog, HasPublicUlid;

    public const DURATION_UNITS = ['days', 'weeks', 'months'];

    public const PAYMENT_METHODS = ['invoice', 'cod'];

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

    /** @return BelongsTo<User, $this> */
    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_user_id');
    }

    /** @return HasMany<QuotationLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(QuotationLine::class)->orderBy('position');
    }

    /** @return HasOne<SalesOrder, $this> */
    public function salesOrder(): HasOne
    {
        return $this->hasOne(SalesOrder::class);
    }

    /** @return HasOne<DeliveryOrder, $this> */
    public function deliveryOrder(): HasOne
    {
        return $this->hasOne(DeliveryOrder::class);
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->orderBy('id');
    }

    /** @return HasOne<Invoice, $this> */
    public function downPaymentInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->where('type', InvoiceType::DownPayment);
    }

    /** @return HasOne<Invoice, $this> */
    public function finalInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->where('type', InvoiceType::Final);
    }

    /** @return HasOne<Inquiry, $this> */
    public function inquiry(): HasOne
    {
        return $this->hasOne(Inquiry::class);
    }

    protected function casts(): array
    {
        return [
            'status' => QuotationStatus::class,
            'discount_pct' => 'decimal:2',
            'ppn_rate' => 'integer',
            'dp_amount' => 'integer',
            'customer_po_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
