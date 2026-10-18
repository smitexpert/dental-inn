<?php

declare(strict_types=1);

namespace App\Http\Requests\Prescriptions;

use Illuminate\Foundation\Http\FormRequest;

class QuickStoreMedicineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-prescriptions');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'strength' => ['nullable', 'string', 'max:64'],
            'form' => ['nullable', 'string', 'max:64'],
            'name_bn' => ['nullable', 'string', 'max:255'],
        ];
    }
}
