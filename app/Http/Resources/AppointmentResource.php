<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Appointment
 */
class AppointmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $doctor = $this->doctor;
        $user = $doctor?->user;
        $patient = $this->patient;

        return [
            'id' => $this->id,
            'doctor_id' => $this->doctor_id,
            'doctor_name' => $user?->name,
            'doctor_color' => $doctor?->color,
            'patient_id' => $this->patient_id,
            'patient' => $patient ? [
                'id' => $patient->id,
                'patient_code' => $patient->patient_code,
                'name' => $patient->name,
                'phone' => $patient->phone,
                'email' => $patient->email,
            ] : null,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'end_at' => $this->scheduled_at ? $this->endAt()->toIso8601String() : null,
            'duration_minutes' => $this->duration_minutes,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'notes' => $this->notes,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancelled_reason' => $this->cancelled_reason,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
