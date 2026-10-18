<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DosageFrequency;
use App\Enums\MealRelation;
use Database\Factories\PrescriptionItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $prescription_id
 * @property ?int $medicine_id
 * @property string $medicine_name
 * @property ?string $strength
 * @property string $dosage
 * @property string $frequency
 * @property ?string $frequency_label_bn
 * @property ?string $duration
 * @property string $meal_relation
 * @property ?string $meal_relation_label_bn
 * @property ?string $instructions
 * @property int $quantity
 * @property int $sort_order
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Prescription $prescription
 * @property-read ?Medicine $medicine
 */
class PrescriptionItem extends Model
{
    /** @use HasFactory<PrescriptionItemFactory> */
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'medicine_id',
        'medicine_name',
        'strength',
        'dosage',
        'frequency',
        'frequency_label_bn',
        'duration',
        'meal_relation',
        'meal_relation_label_bn',
        'instructions',
        'quantity',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    /**
     * Bangla-rendered summary line, e.g.
     * "এসিক্লোফেনাক ১০০ মিগ্রা — ১ টি · দিনে ২ বার · ৫ দিন · খাবারের পরে".
     */
    public function getSummaryBnAttribute(): string
    {
        $name = trim(($this->medicine_name ?? '').' '.($this->strength ?? ''));
        $pieces = array_filter([
            $name,
            $this->dosage,
            $this->frequency_label_bn ?? (DosageFrequency::tryFrom((string) $this->frequency)?->labelBn() ?? ''),
            $this->duration,
            $this->meal_relation_label_bn ?? (MealRelation::tryFrom((string) $this->meal_relation)?->labelBn() ?? ''),
        ], fn ($v) => is_string($v) && trim($v) !== '');

        return implode(' · ', $pieces);
    }
}
