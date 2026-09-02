<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TransferFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $from_account_id
 * @property int $to_account_id
 * @property string $amount
 * @property Carbon $occurred_at
 * @property ?string $reference
 * @property ?string $note
 * @property string $transfer_group_id
 * @property int $performed_by
 * @property-read BankAccount $fromAccount
 * @property-read BankAccount $toAccount
 * @property-read User $performer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CashMovement> $movements
 */
class Transfer extends Model
{
    /** @use HasFactory<TransferFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'from_account_id',
        'to_account_id',
        'amount',
        'occurred_at',
        'reference',
        'note',
        'transfer_group_id',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'occurred_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['from_account_id', 'to_account_id', 'amount', 'occurred_at', 'reference'])
            ->dontLogEmptyChanges();
    }

    /** @return BelongsTo<BankAccount, $this> */
    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'from_account_id');
    }

    /** @return BelongsTo<BankAccount, $this> */
    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'to_account_id');
    }

    /** @return BelongsTo<User, $this> */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /** @return HasMany<CashMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class, 'transfer_group_id', 'transfer_group_id');
    }
}
