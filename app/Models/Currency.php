<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $code
 * @property string $name
 * @property string $symbol
 * @property int $decimals
 * @property bool $is_active
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ExchangeRate> $outgoingRates
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ExchangeRate> $incomingRates
 * @property-read ?CashDrawer $drawer
 */
class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimals',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'decimals' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<ExchangeRate, $this> */
    public function outgoingRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'from_currency_code');
    }

    /** @return HasMany<ExchangeRate, $this> */
    public function incomingRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'to_currency_code');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasOne<CashDrawer, $this> */
    public function drawer(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CashDrawer::class, 'currency_code');
    }

    /**
     * @param  Builder<Currency>  $query
     * @return Builder<Currency>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function formatAmount(float|int|string $amount): string
    {
        $value = (float) $amount;
        $formatted = number_format($value, $this->decimals, '.', ',');

        return $this->symbol.$formatted;
    }
}
