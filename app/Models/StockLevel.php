<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StockLevelFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $product_id
 * @property int $location_id
 * @property int $quantity
 * @property ?Carbon $last_counted_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Product $product
 * @property-read StockLocation $location
 */
class StockLevel extends Model
{
    /** @use HasFactory<StockLevelFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'location_id',
        'quantity',
        'last_counted_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'last_counted_at' => 'datetime',
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

    /**
     * @param  Builder<StockLevel>  $query
     * @return Builder<StockLevel>
     */
    public function scopeAtLocation(Builder $query, int $locationId): Builder
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * @param  Builder<StockLevel>  $query
     * @return Builder<StockLevel>
     */
    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }
}
