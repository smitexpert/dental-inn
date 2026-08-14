<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkingHoursRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('manage-settings'); }

    public function rules(): array
    {
        return [
            'working_hours'               => ['required', 'array'],
            'working_hours.mon'           => ['array'],
            'working_hours.tue'           => ['array'],
            'working_hours.wed'           => ['array'],
            'working_hours.thu'           => ['array'],
            'working_hours.fri'           => ['array'],
            'working_hours.sat'           => ['array'],
            'working_hours.sun'           => ['array'],
            'working_hours.*.*.start'     => ['required_with:working_hours.*.*.end', 'date_format:H:i'],
            'working_hours.*.*.end'       => ['required_with:working_hours.*.*.start', 'date_format:H:i', 'after:working_hours.*.*.start'],
        ];
    }
}
