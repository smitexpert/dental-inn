<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClinicSettingsRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('manage-settings'); }

    public function rules(): array
    {
        return [
            'clinic_name'             => ['required', 'string', 'max:255'],
            'clinic_tagline'          => ['nullable', 'string', 'max:255'],
            'address_line_1'          => ['nullable', 'string', 'max:255'],
            'address_line_2'          => ['nullable', 'string', 'max:255'],
            'city'                    => ['nullable', 'string', 'max:128'],
            'state'                   => ['nullable', 'string', 'max:128'],
            'postal_code'             => ['nullable', 'string', 'max:32'],
            'country'                 => ['required', 'string', 'size:2'],
            'phone'                   => ['nullable', 'string', 'max:32'],
            'email'                   => ['nullable', 'email', 'max:255'],
            'currency_code'           => ['required', 'string', 'size:3'],
            'currency_symbol'         => ['required', 'string', 'max:8'],
            'currency_decimals'       => ['required', 'integer', 'min:0', 'max:4'],
            'currency_position'       => ['required', 'in:before,after'],
            'timezone'                => ['required', 'string', 'timezone'],
            'date_format'             => ['required', 'string'],
            'time_format'             => ['required', 'string'],
            'fiscal_year_start'       => ['required', 'string', 'regex:/^\d{2}-\d{2}$/'],
            'default_tax_rate'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'invoice_prefix'          => ['required', 'string', 'max:16'],
            'next_invoice_number'     => ['required', 'integer', 'min:1'],
            'appointment_slot_minutes' => ['required', 'in:15,30,60'],
        ];
    }
}
