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
 * @property int $account_id
 * @property CashMovementType $type
 * @property string $amount
 * @property ?int $counter_account_id
 * @property ?string $counter_amount
 * @property ?string $transfer_group_id
 * @property ?string $reason
 * @property ?string $reference_type
 * @property ?int $reference_id
 * @property int $performed_by
 * @property ?Carbon $created_at
 * @property-read BankAccount $account
 * @property-read ?BankAccount $counterAccount
 * @property-read User $performer
 * @property-read ?Model $reference
 */
class CashMovement extends Model
{
    /** @use HasFactory<CashMovementFactory> */
    use HasFactory, LogsActivity;

    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'type',
        'amount',
        'counter_account_id',
        'counter_amount',
        'transfer_group_id',
        'reason',
        'reference_type',
        'reference_id',
        'performed_by',
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

    /** @return BelongsTo<BankAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'account_id');
    }

    /** @return BelongsTo<BankAccount, $this> */
    public function counterAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'counter_account_id');
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
            ->logOnly(['type', 'amount', 'reason'])
            ->dontLogEmptyChanges();
    }

    /**
     * @param  Builder<CashMovement>  $query
     * @return Builder<CashMovement>
     */
    public function scopeForAccount(Builder $query, int $accountId): Builder
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * @param  Builder<CashMovement>  $query
     * @return Builder<CashMovement>
     */
    public function scopeOfType(Builder $query, CashMovementType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    /**
     * @param  Builder<CashMovement>  $query
     * @return Builder<CashMovement>
     */
    public function scopeForGroup(Builder $query, string $groupId): Builder
    {
        return $query->where('transfer_group_id', $groupId);
    }
}
