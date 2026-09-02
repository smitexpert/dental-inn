<?php

declare(strict_types=1);

namespace App\Http\Requests\Doctors;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-doctors');
    }

    public function rules(): array
    {
        return [
            'schedules' => ['required', 'array', 'min:0', 'max:7'],
            'schedules.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time' => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'schedules.*.end_time.after' => 'The end time must be after the start time.',
        ];
    }
}
