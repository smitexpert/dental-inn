<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Enums\DiscountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-invoices');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $discountTypes = array_map(fn (DiscountType $t) => $t->value, DiscountType::cases());

        return [
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'treatment_record_id' => ['nullable', 'integer', 'exists:treatment_records,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_type' => ['required', Rule::in($discountTypes)],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.treatment_record_id' => ['nullable', 'integer', 'exists:treatment_records,id'],
        ];
    }
}
