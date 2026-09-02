<?php

declare(strict_types=1);

namespace App\Http\Requests\Patients;

use Illuminate\Foundation\Http\FormRequest;

class StoreTreatmentRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-treatment-records');
    }

    public function rules(): array
    {
        return [
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'visited_at' => ['required', 'date'],
            'chief_complaint' => ['nullable', 'string', 'max:5000'],
            'diagnosis' => ['nullable', 'string'],
            'treatment_notes' => ['nullable', 'string'],
            'tooth_chart' => ['nullable', 'array'],
            'tooth_chart.teeth' => ['array'],
            'tooth_chart.teeth.*.fdi' => ['required_with:tooth_chart.teeth.*', 'integer', 'min:11', 'max:85'],
            'tooth_chart.teeth.*.procedures' => ['array'],
            'tooth_chart.teeth.*.procedures.*.code' => ['required_with:tooth_chart.teeth.*.procedures.*', 'string', 'max:32'],
            'tooth_chart.teeth.*.procedures.*.name' => ['required_with:tooth_chart.teeth.*.procedures.*', 'string', 'max:255'],
            'tooth_chart.teeth.*.procedures.*.surface' => ['nullable', 'string', 'max:32'],
            'follow_up_date' => ['nullable', 'date'],
            'status' => ['required', 'in:in_progress,completed,cancelled'],
        ];
    }
}
