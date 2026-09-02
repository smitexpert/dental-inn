<?php

declare(strict_types=1);

namespace App\Http\Requests\Bank;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReconcileAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reconcile-accounts') ?? false;
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', 'integer', Rule::exists('bank_accounts', 'id')],
            'counted_balance' => ['required', 'numeric', 'min:0'],
        ];
    }
}
