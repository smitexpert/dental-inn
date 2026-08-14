<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-suppliers');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Supplier|null $supplier */
        $supplier = $this->route('supplier');
        $supplierId = $supplier?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')->ignore($supplierId)],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
