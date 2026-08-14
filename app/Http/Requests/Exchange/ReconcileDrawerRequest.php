<?php

declare(strict_types=1);

namespace App\Http\Requests\Exchange;

use Illuminate\Foundation\Http\FormRequest;

class ReconcileDrawerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reconcile-cash-drawer');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'currency_code' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'counted_balance' => ['required', 'numeric', 'min:0'],
        ];
    }
}
