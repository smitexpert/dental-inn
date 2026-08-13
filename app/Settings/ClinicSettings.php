<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ClinicSettings extends Settings
{
    public string $clinic_name;
    public string $clinic_tagline;
    public ?string $logo_media_id;
    public string $address_line_1;
    public ?string $address_line_2;
    public string $city;
    public ?string $state;
    public string $postal_code;
    public string $country;
    public string $phone;
    public ?string $email;
    public string $currency_code;
    public string $currency_symbol;
    public int $currency_decimals;
    public string $currency_position;
    public string $timezone;
    public string $date_format;
    public string $time_format;
    public string $fiscal_year_start;
    public ?float $default_tax_rate;
    public string $invoice_prefix;
    public int $next_invoice_number;
    public string $appointment_slot_minutes;
    public string $working_hours_json;

    public static function group(): string
    {
        return 'clinic';
    }
}
