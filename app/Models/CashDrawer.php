<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CashDrawerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $currency_code
 * @property string $balance
 * @property ?Carbon $last_reconciled_at
 * @property ?string $last_reconciled_balance
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Currency $currency
 * @property-read Collection<int, CashMovement> $movements
 */
class CashDrawer extends Model
{
    /** @use HasFactory<CashDrawerFactory> */
    use HasFactory;

    protected $fillable = [
        'currency_code',
        'balance',
        'last_reconciled_at',
        'last_reconciled_balance',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:4',
            'last_reconciled_balance' => 'decimal:4',
            'last_reconciled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Currency, $this> */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code');
    }

    /** @return HasMany<CashMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class, 'currency_code');
    }

    /**
     * @param  Builder<CashDrawer>  $query
     * @return Builder<CashDrawer>
     */
    public function scopeForCurrency(Builder $query, string $code): Builder
    {
        return $query->where('currency_code', $code);
    }
}
