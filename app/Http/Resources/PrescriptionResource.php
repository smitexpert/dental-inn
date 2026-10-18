<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Prescription
 */
class PrescriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $doctor = $this->doctor;
        $user = $doctor?->user;

        return [
            'id' => $this->id,
            'prescription_number' => $this->prescription_number,
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'doctor_name' => $user?->name,
            'doctor_color' => $doctor?->color,
            'appointment_id' => $this->appointment_id,
            'treatment_record_id' => $this->treatment_record_id,
            'issued_at' => $this->issued_at?->toIso8601String(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_label_bn' => $this->status->labelBn(),
            'general_advice' => $this->general_advice,
            'diagnosis_text' => $this->diagnosis_text,
            'is_editable' => $this->isEditable(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'items' => PrescriptionItemResource::collection($this->whenLoaded('items'))->resolve(),
        ];
    }
}
