<?php

declare(strict_types=1);

namespace App\Http\Requests\Patients;

use App\Http\Requests\Patients\Concerns\DerivesDateOfBirthFromAge;
use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
{
    use DerivesDateOfBirthFromAge;

    public function authorize(): bool
    {
        return $this->user()->can('manage-patients');
    }

    protected function prepareForValidation(): void
    {
        $this->applyAgeToDateOfBirth();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'age' => $this->ageRules(),
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:128'],
            'state' => ['nullable', 'string', 'max:128'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'country' => ['nullable', 'string', 'max:64'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:32'],
            'medical_history' => ['nullable', 'string'],
            'dental_history' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
