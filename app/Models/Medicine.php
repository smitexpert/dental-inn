<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MedicineFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property ?string $strength
 * @property ?string $form
 * @property ?string $name_bn
 * @property bool $is_active
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Collection<int, PrescriptionItem> $prescriptionItems
 */
class Medicine extends Model
{
    /** @use HasFactory<MedicineFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'strength',
        'form',
        'name_bn',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Human-readable label used by the medicine picker dropdown.
     * e.g. "Aceclofenac 100mg (Tablet)" or "Napa 500mg".
     */
    public function getDisplayLabelAttribute(): string
    {
        $parts = array_filter([$this->name, $this->strength, $this->form], fn ($v) => is_string($v) && $v !== '');

        return implode(' ', $parts) ?: (string) $this->name;
    }

    /**
     * @return HasMany<PrescriptionItem, $this>
     */
    public function prescriptionItems(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    /**
     * @param  Builder<Medicine>  $query
     * @return Builder<Medicine>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Case-insensitive prefix match on name with strength/form as tie-breakers.
     *
     * @param  Builder<Medicine>  $query
     * @return Builder<Medicine>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $like = '%'.$term.'%';
        $prefix = $term.'%';

        return $query->where(function (Builder $w) use ($like, $prefix): void {
            $w->where('name', 'like', $prefix)
                ->orWhere('name', 'like', $like)
                ->orWhere('strength', 'like', $like);
        });
    }
}
