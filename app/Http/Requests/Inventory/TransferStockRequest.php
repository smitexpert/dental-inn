<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class TransferStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('record-stock-movements');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from_location_id' => ['required', 'integer', 'exists:stock_locations,id'],
            'to_location_id' => ['required', 'integer', 'exists:stock_locations,id', 'different:from_location_id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if ((int) $this->input('from_location_id') === (int) $this->input('to_location_id')) {
                $v->errors()->add('to_location_id', 'Destination must differ from source.');
            }
        });
    }
}
