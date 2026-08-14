<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockLocationType;
use Database\Factories\StockLocationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property StockLocationType $location_type
 * @property bool $is_active
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StockLevel> $stockLevels
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StockMovement> $movements
 */
class StockLocation extends Model
{
    /** @use HasFactory<StockLocationFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'location_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'location_type' => StockLocationType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<StockLevel, $this>
     */
    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class, 'location_id');
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'location_id');
    }

    /**
     * @param  Builder<StockLocation>  $query
     * @return Builder<StockLocation>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
