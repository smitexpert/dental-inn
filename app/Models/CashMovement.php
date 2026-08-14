<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CashMovementType;
use Database\Factories\CashMovementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use RuntimeException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $currency_code
 * @property CashMovementType $type
 * @property string $amount
 * @property ?int $exchange_rate_id
 * @property ?string $counter_currency_code
 * @property ?string $counter_amount
 * @property ?string $reason
 * @property ?string $reference_type
 * @property ?int $reference_id
 * @property int $performed_by
 * @property ?string $exchange_group_id
 * @property ?Carbon $created_at
 * @property-read Currency $currency
 * @property-read ?Currency $counterCurrency
 * @property-read ?ExchangeRate $exchangeRate
 * @property-read User $performer
 * @property-read ?Model $reference
 */
class CashMovement extends Model
{
    /** @use HasFactory<CashMovementFactory> */
    use HasFactory, LogsActivity;

    public $timestamps = false;

    protected $fillable = [
        'currency_code',
        'type',
        'amount',
        'exchange_rate_id',
        'counter_currency_code',
        'counter_amount',
        'reason',
        'reference_type',
        'reference_id',
        'performed_by',
        'exchange_group_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => CashMovementType::class,
            'amount' => 'decimal:4',
            'counter_amount' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new RuntimeException('CashMovement rows are immutable.');
        });
    }

    /** @return BelongsTo<Currency, $this> */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code');
    }

    /** @return BelongsTo<Currency, $this> */
    public function counterCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'counter_currency_code');
    }

    /** @return BelongsTo<ExchangeRate, $this> */
    public function exchangeRate(): BelongsTo
    {
        return $this->belongsTo(ExchangeRate::class);
    }

    /** @return BelongsTo<User, $this> */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'amount', 'reason', 'currency_code'])
            ->dontLogEmptyChanges();
    }

    /**
     * @param  Builder<CashMovement>  $query
     * @return Builder<CashMovement>
     */
    public function scopeForCurrency(Builder $query, string $code): Builder
    {
        return $query->where('currency_code', $code);
    }

    /**
     * @param  Builder<CashMovement>  $query
     * @return Builder<CashMovement>
     */
    public function scopeOfType(Builder $query, CashMovementType $type): Builder
    {
        return $query->where('type', $type->value);
    }
}
