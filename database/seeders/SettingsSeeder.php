<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Settings\ClinicSettings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'clinic_name' => 'My Dental Clinic',
            'clinic_tagline' => 'Caring for your smile',
            'logo_media_id' => null,
            'address_line_1' => '',
            'address_line_2' => null,
            'city' => '',
            'state' => null,
            'postal_code' => '',
            'country' => 'BD',
            'phone' => '',
            'email' => null,
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
            'currency_decimals' => 0,
            'currency_position' => 'before',
            'timezone' => 'Asia/Dhaka',
            'date_format' => 'Y-m-d',
            'time_format' => 'h:i A',
            'fiscal_year_start' => '01-01',
            'default_tax_rate' => null,
            'invoice_prefix' => 'INV-',
            'next_invoice_number' => 1,
            'appointment_slot_minutes' => '30',
            'appointment_buffer_minutes' => 0,
            'working_hours_json' => json_encode([
                'mon' => [['start' => '09:00', 'end' => '18:00']],
                'tue' => [['start' => '09:00', 'end' => '18:00']],
                'wed' => [['start' => '09:00', 'end' => '18:00']],
                'thu' => [['start' => '09:00', 'end' => '18:00']],
                'fri' => [['start' => '09:00', 'end' => '18:00']],
                'sat' => [['start' => '09:00', 'end' => '14:00']],
                'sun' => [],
            ]),
        ];

        app(ClinicSettings::class)->fill($defaults)->save();
    }
}
