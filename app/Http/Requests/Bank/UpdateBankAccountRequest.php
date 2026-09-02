<?php

declare(strict_types=1);

namespace App\Http\Requests\Bank;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-accounts') ?? false;
    }

    public function rules(): array
    {
        $account = $this->route('account');

        $rules = [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'branch' => ['nullable', 'string', 'max:120'],
            'is_active' => ['boolean'],
        ];

        if ($this->input('type') === 'bank' || ($account && $account->type === 'bank')) {
            $rules['bank_name'] = ['sometimes', 'required', 'string', 'max:120'];
            $rules['account_name'] = ['sometimes', 'required', 'string', 'max:120'];
            $rules['account_number'] = [
                'sometimes',
                'required',
                'string',
                'max:64',
                Rule::unique('bank_accounts', 'account_number')
                    ->where('bank_name', $this->input('bank_name', $account?->bank_name))
                    ->ignore($account?->id),
            ];
        }

        return $rules;
    }
}
