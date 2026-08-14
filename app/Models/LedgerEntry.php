<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $entry_date
 * @property string $description
 * @property int $debit_account_id
 * @property int $credit_account_id
 * @property string $amount
 * @property string $currency_code
 * @property string $exchange_rate
 * @property ?string $reference_type
 * @property ?int $reference_id
 * @property ?int $created_by
 * @property ?Carbon $created_at
 * @property-read Account $debitAccount
 * @property-read Account $creditAccount
 * @property-read ?Model $reference
 * @property-read ?User $creator
 */
class LedgerEntry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'entry_date',
        'description',
        'debit_account_id',
        'credit_account_id',
        'amount',
        'currency_code',
        'exchange_rate',
        'reference_type',
        'reference_id',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'amount' => 'decimal:4',
            'exchange_rate' => 'decimal:8',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Account, $this> */
    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }

    /** @return BelongsTo<Account, $this> */
    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'credit_account_id');
    }

    /** @return MorphTo<Model, $this> */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
