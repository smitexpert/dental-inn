<?php

declare(strict_types=1);

namespace App\Http\Requests\Appointments;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-appointments');
    }

    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'patient_name' => ['required', 'string', 'max:255'],
            'patient_phone' => ['required', 'string', 'max:32'],
            'patient_id' => ['nullable', 'integer'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
