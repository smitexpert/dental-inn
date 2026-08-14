<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockMovementType;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $product_id
 * @property ?int $location_id
 * @property StockMovementType $type
 * @property int $quantity
 * @property ?string $reason
 * @property ?string $reference_type
 * @property ?int $reference_id
 * @property int $performed_by
 * @property ?string $unit_cost
 * @property ?Carbon $created_at
 * @property-read Product $product
 * @property-read ?StockLocation $location
 * @property-read User $performer
 * @property-read ?Model $reference
 */
class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory, LogsActivity;

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'location_id',
        'type',
        'quantity',
        'reason',
        'reference_type',
        'reference_id',
        'performed_by',
        'unit_cost',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity' => 'integer',
            'unit_cost' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }

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
            ->logOnly(['type', 'quantity', 'reason', 'location_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
