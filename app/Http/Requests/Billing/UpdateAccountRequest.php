<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-chart-of-accounts');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $types = array_map(fn (AccountType $t) => $t->value, AccountType::cases());
        $id = (int) $this->route('account');

        return [
            'code' => ['required', 'string', 'max:16', 'unique:accounts,code,'.$id],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in($types)],
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id', 'not_in:'.$id],
            'is_active' => ['boolean'],
        ];
    }
}
