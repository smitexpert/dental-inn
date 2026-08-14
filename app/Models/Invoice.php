<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $invoice_number
 * @property ?int $patient_id
 * @property ?int $appointment_id
 * @property ?int $treatment_record_id
 * @property string $invoice_date
 * @property string $due_date
 * @property InvoiceStatus $status
 * @property string $subtotal
 * @property string $tax_rate
 * @property string $tax_amount
 * @property DiscountType $discount_type
 * @property string $discount_value
 * @property string $discount_amount
 * @property string $total
 * @property string $paid_amount
 * @property ?string $notes
 * @property ?int $created_by
 * @property ?Carbon $issued_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property ?Carbon $deleted_at
 * @property-read ?Patient $patient
 * @property-read ?Appointment $appointment
 * @property-read ?TreatmentRecord $treatmentRecord
 * @property-read Collection<int, InvoiceItem> $items
 * @property-read Collection<int, Payment> $payments
 * @property-read ?User $creator
 * @property-read string $balance_due
 */
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'patient_id',
        'appointment_id',
        'treatment_record_id',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_type',
        'discount_value',
        'discount_amount',
        'total',
        'paid_amount',
        'notes',
        'created_by',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'status' => InvoiceStatus::class,
            'discount_type' => DiscountType::class,
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<Appointment, $this> */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /** @return BelongsTo<TreatmentRecord, $this> */
    public function treatmentRecord(): BelongsTo
    {
        return $this->belongsTo(TreatmentRecord::class);
    }

    /** @return HasMany<InvoiceItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderByDesc('payment_date');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getBalanceDueAttribute(): string
    {
        return number_format(
            (float) $this->total - (float) $this->paid_amount,
            2,
            '.',
            '',
        );
    }

    public function isFullyPaid(): bool
    {
        return (float) $this->paid_amount >= (float) $this->total;
    }

    public function hasPayments(): bool
    {
        return $this->paid_amount > 0;
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            InvoiceStatus::Issued->value,
            InvoiceStatus::PartiallyPaid->value,
            InvoiceStatus::Overdue->value,
        ]);
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeOverdueCandidates(Builder $query): Builder
    {
        return $query->whereIn('status', [
            InvoiceStatus::Issued->value,
            InvoiceStatus::PartiallyPaid->value,
        ])->where('due_date', '<', now()->toDateString());
    }
}
