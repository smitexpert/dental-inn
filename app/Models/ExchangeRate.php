<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ExchangeRateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $from_currency_code
 * @property string $to_currency_code
 * @property string $rate
 * @property Carbon $effective_at
 * @property ?int $created_by
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Currency $fromCurrency
 * @property-read Currency $toCurrency
 * @property-read ?User $creator
 */
class ExchangeRate extends Model
{
    /** @use HasFactory<ExchangeRateFactory> */
    use HasFactory;

    protected $fillable = [
        'from_currency_code',
        'to_currency_code',
        'rate',
        'effective_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_at' => 'datetime',
            'rate' => 'decimal:8',
        ];
    }

    /** @return BelongsTo<Currency, $this> */
    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'from_currency_code');
    }

    /** @return BelongsTo<Currency, $this> */
    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency_code');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isInverseOf(self $other): bool
    {
        return $this->from_currency_code === $other->to_currency_code
            && $this->to_currency_code === $other->from_currency_code;
    }

    /**
     * @param  Builder<ExchangeRate>  $query
     * @return Builder<ExchangeRate>
     */
    public function scopeForPair(Builder $query, string $from, string $to): Builder
    {
        return $query->where('from_currency_code', $from)
            ->where('to_currency_code', $to);
    }

    /**
     * @param  Builder<ExchangeRate>  $query
     * @return Builder<ExchangeRate>
     */
    public function scopeEffectiveAtOrBefore(Builder $query, Carbon $at): Builder
    {
        return $query->where('effective_at', '<=', $at);
    }
}
