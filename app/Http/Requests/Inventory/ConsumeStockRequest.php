<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class ConsumeStockRequest extends FormRequest
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
            'location_id' => ['required', 'integer', 'exists:stock_locations,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
        ];
    }
}
