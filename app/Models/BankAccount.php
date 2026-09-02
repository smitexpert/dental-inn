<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BankAccountFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $name
 * @property string $type
 * @property ?string $bank_name
 * @property ?string $account_name
 * @property ?string $branch
 * @property ?string $account_number
 * @property string $currency_code
 * @property string $opening_balance
 * @property string $balance
 * @property bool $is_system
 * @property bool $is_active
 * @property ?Carbon $last_reconciled_at
 * @property ?string $last_reconciled_balance
 * @property ?int $created_by
 * @property-read User $creator
 * @property-read Collection<int, CashMovement> $movements
 */
class BankAccount extends Model
{
    /** @use HasFactory<BankAccountFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'type',
        'bank_name',
        'account_name',
        'branch',
        'account_number',
        'currency_code',
        'opening_balance',
        'balance',
        'is_system',
        'is_active',
        'last_reconciled_at',
        'last_reconciled_balance',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:4',
            'balance' => 'decimal:4',
            'last_reconciled_balance' => 'decimal:4',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'last_reconciled_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'bank_name', 'account_name', 'account_number', 'balance', 'is_active'])
            ->dontLogEmptyChanges();
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<CashMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class, 'account_id');
    }

    /**
     * @param  Builder<BankAccount>  $query
     * @return Builder<BankAccount>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
