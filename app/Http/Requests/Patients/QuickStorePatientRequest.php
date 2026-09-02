<?php

declare(strict_types=1);

namespace App\Http\Requests\Patients;

use Illuminate\Foundation\Http\FormRequest;

class QuickStorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-patients');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'age' => ['nullable', 'integer', 'min:0', 'max:130'],
        ];
    }
}
