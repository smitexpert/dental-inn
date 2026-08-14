<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-expenses');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $methods = array_map(fn (PaymentMethod $m) => $m->value, PaymentMethod::cases());

        return [
            'expense_date' => ['required', 'date'],
            'category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency_code' => ['required', 'string', 'size:3'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', Rule::in($methods)],
            'vendor' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'receipt' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ];
    }
}
