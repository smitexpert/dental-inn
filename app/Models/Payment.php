<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $invoice_id
 * @property string $payment_date
 * @property string $amount
 * @property string $currency_code
 * @property string $exchange_rate
 * @property PaymentMethod $method
 * @property ?string $reference
 * @property ?string $notes
 * @property ?int $received_by
 * @property ?Carbon $created_at
 * @property-read Invoice $invoice
 * @property-read ?User $receiver
 */
class Payment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'payment_date',
        'amount',
        'currency_code',
        'exchange_rate',
        'method',
        'reference',
        'notes',
        'received_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'exchange_rate' => 'decimal:8',
            'method' => PaymentMethod::class,
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<User, $this> */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
