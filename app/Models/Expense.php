<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property Carbon|string $expense_date
 * @property int $category_id
 * @property string $amount
 * @property string $currency_code
 * @property string $exchange_rate
 * @property PaymentMethod $payment_method
 * @property ?string $vendor
 * @property ?string $description
 * @property ?string $receipt_path
 * @property ExpenseStatus $status
 * @property ?int $created_by
 * @property ?int $approved_by
 * @property ?Carbon $approved_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read ExpenseCategory $category
 * @property-read ?User $creator
 * @property-read ?User $approver
 */
class Expense extends Model implements HasMedia
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'expense_date',
        'category_id',
        'amount',
        'currency_code',
        'exchange_rate',
        'payment_method',
        'vendor',
        'description',
        'receipt_path',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
            'exchange_rate' => 'decimal:8',
            'payment_method' => PaymentMethod::class,
            'status' => ExpenseStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('receipt')->singleFile();
    }

    /** @return BelongsTo<ExpenseCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ExpenseStatus::Pending->value);
    }
}
