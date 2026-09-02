<?php

declare(strict_types=1);

namespace App\Http\Requests\Bank;

use App\Enums\BankAccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-accounts') ?? false;
    }

    public function rules(): array
    {
        $type = $this->input('type');

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::enum(BankAccountType::class)],
            'branch' => ['nullable', 'string', 'max:120'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];

        if ($type === BankAccountType::Bank->value) {
            $rules['bank_name'] = ['required', 'string', 'max:120'];
            $rules['account_name'] = ['required', 'string', 'max:120'];
            $rules['account_number'] = [
                'required',
                'string',
                'max:64',
                Rule::unique('bank_accounts', 'account_number')
                    ->where('bank_name', $this->input('bank_name')),
            ];
        }

        return $rules;
    }
}
