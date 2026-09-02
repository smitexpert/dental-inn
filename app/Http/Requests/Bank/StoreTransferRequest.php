<?php

declare(strict_types=1);

namespace App\Http\Requests\Bank;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('record-transfers') ?? false;
    }

    public function rules(): array
    {
        return [
            'from_account_id' => ['required', 'integer', Rule::exists('bank_accounts', 'id')],
            'to_account_id' => [
                'required',
                'integer',
                'different:from_account_id',
                Rule::exists('bank_accounts', 'id'),
            ],
            'amount' => ['required', 'numeric', 'gt:0'],
            'occurred_at' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
