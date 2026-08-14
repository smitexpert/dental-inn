<?php

declare(strict_types=1);

namespace App\Http\Requests\Exchange;

use Illuminate\Foundation\Http\FormRequest;

class StoreExchangeRateRequest extends FormRequest
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
            'from_currency_code' => ['required', 'string', 'size:3', 'different:to_currency_code', 'exists:currencies,code'],
            'to_currency_code' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'rate' => ['required', 'numeric', 'gt:0'],
            'effective_at' => ['required', 'date'],
        ];
    }
}
