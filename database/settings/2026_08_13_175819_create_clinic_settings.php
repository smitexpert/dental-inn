<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('clinic.clinic_name', 'My Dental Clinic');
        $this->migrator->add('clinic.clinic_tagline', 'Caring for your smile');
        $this->migrator->add('clinic.logo_media_id', null);
        $this->migrator->add('clinic.address_line_1', '');
        $this->migrator->add('clinic.address_line_2', null);
        $this->migrator->add('clinic.city', '');
        $this->migrator->add('clinic.state', null);
        $this->migrator->add('clinic.postal_code', '');
        $this->migrator->add('clinic.country', 'BD');
        $this->migrator->add('clinic.phone', '');
        $this->migrator->add('clinic.email', null);
        $this->migrator->add('clinic.currency_code', 'BDT');
        $this->migrator->add('clinic.currency_symbol', '৳');
        $this->migrator->add('clinic.currency_decimals', 0);
        $this->migrator->add('clinic.currency_position', 'before');
        $this->migrator->add('clinic.timezone', 'Asia/Dhaka');
        $this->migrator->add('clinic.date_format', 'Y-m-d');
        $this->migrator->add('clinic.time_format', 'h:i A');
        $this->migrator->add('clinic.fiscal_year_start', '01-01');
        $this->migrator->add('clinic.default_tax_rate', null);
        $this->migrator->add('clinic.invoice_prefix', 'INV-');
        $this->migrator->add('clinic.next_invoice_number', 1);
        $this->migrator->add('clinic.appointment_slot_minutes', '30');
        $this->migrator->add('clinic.working_hours_json', json_encode([
            'mon' => [['start' => '09:00', 'end' => '18:00']],
            'tue' => [['start' => '09:00', 'end' => '18:00']],
            'wed' => [['start' => '09:00', 'end' => '18:00']],
            'thu' => [['start' => '09:00', 'end' => '18:00']],
            'fri' => [['start' => '09:00', 'end' => '18:00']],
            'sat' => [['start' => '09:00', 'end' => '14:00']],
            'sun' => [],
        ]));
    }
};
