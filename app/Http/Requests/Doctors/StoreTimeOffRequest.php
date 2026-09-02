<?php

declare(strict_types=1);

namespace App\Http\Requests\Doctors;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeOffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-doctors');
    }

    public function rules(): array
    {
        return [
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
