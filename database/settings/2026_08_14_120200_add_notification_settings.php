<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('clinic.notification_default_channel', 'sms');
        $this->migrator->add('clinic.appointment_reminder_24h_enabled', true);
        $this->migrator->add('clinic.appointment_reminder_1h_enabled', true);
        $this->migrator->add('clinic.invoice_notification_enabled', true);
        $this->migrator->add('clinic.payment_notification_enabled', true);
    }
};
