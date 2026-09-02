<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductCategory;
use App\Enums\ProductUnit;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $sku
 * @property ?string $barcode
 * @property string $name
 * @property ProductCategory $category
 * @property ProductUnit $unit
 * @property ?string $cost_price
 * @property ?string $selling_price
 * @property int $reorder_level
 * @property int $reorder_quantity
 * @property ?string $description
 * @property bool $is_active
 * @property bool $track_stock
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property ?Carbon $deleted_at
 * @property-read Collection<int, StockLevel> $stockLevels
 * @property-read Collection<int, StockMovement> $movements
 * @property-read Collection<int, Supplier> $suppliers
 * @property-read int $total_quantity
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'sku',
        'barcode',
        'name',
        'category',
        'unit',
        'cost_price',
        'selling_price',
        'reorder_level',
        'reorder_quantity',
        'description',
        'is_active',
        'track_stock',
    ];

    protected function casts(): array
    {
        return [
            'category' => ProductCategory::class,
            'unit' => ProductUnit::class,
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'reorder_level' => 'integer',
            'reorder_quantity' => 'integer',
            'is_active' => 'boolean',
            'track_stock' => 'boolean',
        ];
    }

    /** @return HasMany<StockLevel, $this> */
    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    /** @return HasMany<StockMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->orderByDesc('created_at');
    }

    /**
     * @return BelongsToMany<Supplier, $this>
     */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'product_supplier')
            ->withPivot(['supplier_sku', 'unit_cost', 'lead_time_days', 'is_preferred'])
            ->withTimestamps();
    }

    public function getTotalQuantityAttribute(): int
    {
        if (! $this->track_stock) {
            return 0;
        }

        return (int) $this->stockLevels()->sum('quantity');
    }

    public function getLowestQuantityAttribute(): int
    {
        if (! $this->track_stock) {
            return 0;
        }

        return (int) ($this->stockLevels()->min('quantity') ?? 0);
    }

    public function isLow(): bool
    {
        if (! $this->track_stock || ! $this->is_active) {
            return false;
        }

        return $this->lowest_quantity <= $this->reorder_level;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['sku', 'name', 'category', 'unit', 'cost_price', 'selling_price', 'reorder_level', 'is_active', 'track_stock'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeTracked(Builder $query): Builder
    {
        return $query->where('track_stock', true);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $w) use ($like): void {
            $w->where('name', 'like', $like)
                ->orWhere('sku', 'like', $like)
                ->orWhere('barcode', 'like', $like)
                ->orWhere('description', 'like', $like);
        });
    }

    /**
     * Restrict the query to products whose minimum stock quantity is
     * less than or equal to their reorder_level. Products that do not
     * track stock are excluded entirely.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->tracked()
            ->whereHas('stockLevels', function (Builder $q): void {
                $q->whereColumn('quantity', '<=', 'products.reorder_level');
            });
    }
}
