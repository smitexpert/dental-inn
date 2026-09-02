<?php

declare(strict_types=1);

namespace App\Http\Requests\Doctors;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-doctors');
    }

    public function rules(): array
    {
        $doctorId = $this->route('doctor')->id;
        $userId = $this->route('doctor')->user_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'is_active' => ['boolean'],
            'specialization' => ['nullable', 'string', 'max:128'],
            'license_number' => ['nullable', 'string', 'max:64'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'years_of_experience' => ['nullable', 'integer', 'min:0', 'max:65'],
            'consultation_fee' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_available' => ['boolean'],
        ];
    }
}
