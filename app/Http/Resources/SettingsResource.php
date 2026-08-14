<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Settings\ClinicSettings;

class SettingsResource
{
    public static function fromSettings(ClinicSettings $s): array
    {
        return [
            'clinic_name' => $s->clinic_name,
            'clinic_tagline' => $s->clinic_tagline,
            'logo_media_id' => $s->logo_media_id,
            'address_line_1' => $s->address_line_1,
            'address_line_2' => $s->address_line_2,
            'city' => $s->city,
            'state' => $s->state,
            'postal_code' => $s->postal_code,
            'country' => $s->country,
            'phone' => $s->phone,
            'email' => $s->email,
            'currency_code' => $s->currency_code,
            'currency_symbol' => $s->currency_symbol,
            'currency_decimals' => $s->currency_decimals,
            'currency_position' => $s->currency_position,
            'timezone' => $s->timezone,
            'date_format' => $s->date_format,
            'time_format' => $s->time_format,
            'fiscal_year_start' => $s->fiscal_year_start,
            'default_tax_rate' => $s->default_tax_rate,
            'invoice_prefix' => $s->invoice_prefix,
            'next_invoice_number' => $s->next_invoice_number,
            'appointment_slot_minutes' => $s->appointment_slot_minutes,
            'working_hours' => json_decode($s->working_hours_json, true) ?? [],
        ];
    }
}
