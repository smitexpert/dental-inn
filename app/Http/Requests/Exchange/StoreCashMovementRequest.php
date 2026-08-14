<?php

declare(strict_types=1);

namespace App\Http\Requests\Exchange;

use App\Enums\CashMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCashMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('record-cash-movements');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $types = array_map(fn (CashMovementType $t) => $t->value, CashMovementType::cases());

        $exchangeTypes = [
            CashMovementType::ExchangeIn->value,
            CashMovementType::ExchangeOut->value,
        ];

        $needsReason = [
            CashMovementType::CashIn->value,
            CashMovementType::CashOut->value,
            CashMovementType::Adjustment->value,
        ];

        $type = (string) $this->input('type');

        return [
            'type' => ['required', Rule::in($types)],
            'currency_code' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => [
                Rule::requiredIf(in_array($type, $needsReason, true)),
                'nullable',
                'string',
                'max:255',
            ],
            'to_currency_code' => [
                Rule::requiredIf(in_array($type, $exchangeTypes, true)),
                'nullable',
                'string',
                'size:3',
                'exists:currencies,code',
                'different:currency_code',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A reason is required for cash in, cash out, and adjustment movements.',
            'to_currency_code.required' => 'Select the currency to exchange into.',
            'to_currency_code.different' => 'Source and destination currencies must differ.',
        ];
    }
}
