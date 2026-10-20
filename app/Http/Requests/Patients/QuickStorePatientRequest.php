<?php

declare(strict_types=1);

namespace App\Http\Requests\Patients;

use App\Http\Requests\Patients\Concerns\DerivesDateOfBirthFromAge;
use Illuminate\Foundation\Http\FormRequest;

class QuickStorePatientRequest extends FormRequest
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
            'gender' => ['nullable', 'in:male,female,other'],
            // Derived from `age` by prepareForValidation(); kept as a rule so
            // the merged value is still checked before it reaches the model.
            'date_of_birth' => ['nullable', 'date'],
        ];
    }
}
