<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-payments');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $methods = array_map(fn (PaymentMethod $m) => $m->value, PaymentMethod::cases());

        return [
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'method' => ['required', Rule::in($methods)],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
