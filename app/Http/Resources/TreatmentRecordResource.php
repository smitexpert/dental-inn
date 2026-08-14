<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TreatmentRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TreatmentRecord
 */
class TreatmentRecordResource extends JsonResource
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
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'doctor_name' => $user?->name,
            'doctor_color' => $doctor?->color,
            'appointment_id' => $this->appointment_id,
            'visited_at' => $this->visited_at->toIso8601String(),
            'chief_complaint' => $this->chief_complaint,
            'diagnosis' => $this->diagnosis,
            'treatment_notes' => $this->treatment_notes,
            'tooth_chart' => $this->tooth_chart,
            'follow_up_date' => $this->follow_up_date?->format('Y-m-d'),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_editable' => $this->isEditable(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
