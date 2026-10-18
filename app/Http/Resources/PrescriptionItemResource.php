<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PrescriptionItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PrescriptionItem
 */
class PrescriptionItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medicine_id' => $this->medicine_id,
            'medicine_name' => $this->medicine_name,
            'strength' => $this->strength,
            'dosage' => $this->dosage,
            'frequency' => $this->frequency,
            'frequency_label_bn' => $this->frequency_label_bn,
            'duration' => $this->duration,
            'meal_relation' => $this->meal_relation,
            'meal_relation_label_bn' => $this->meal_relation_label_bn,
            'instructions' => $this->instructions,
            'quantity' => $this->quantity,
            'sort_order' => $this->sort_order,
            'summary_bn' => $this->summary_bn,
        ];
    }
}
