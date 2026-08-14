<?php

declare(strict_types=1);

namespace App\Http\Requests\Exchange;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExchangeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-exchange-rates');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rate' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'effective_at' => ['sometimes', 'required', 'date'],
        ];
    }
}
