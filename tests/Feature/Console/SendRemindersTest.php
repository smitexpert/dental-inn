<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enums\AppointmentStatus;
use App\Enums\NotificationChannel;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Patient;
use App\Models\User;
use App\Settings\ClinicSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        NotificationTemplate::factory()->create([
            'key' => 'appointment-reminder-24h',
            'channel' => NotificationChannel::Sms,
            'body' => 'Hi {{ patient_name }}',
            'is_active' => true,
        ]);
        NotificationTemplate::factory()->create([
            'key' => 'appointment-reminder-1h',
            'channel' => NotificationChannel::Sms,
            'body' => '1h: {{ patient_name }}',
            'is_active' => true,
        ]);

        $settings = app(ClinicSettings::class);
        $settings->notification_default_channel = 'sms';
        $settings->appointment_reminder_24h_enabled = true;
        $settings->appointment_reminder_1h_enabled = true;
        $settings->save();
    }

    public function test_24h_reminder_sends_for_upcoming_appointment(): void
    {
        $patient = Patient::factory()->create(['phone' => '+8801712345678']);
        $admin = User::factory()->create();
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => Doctor::factory()->create()->id,
            'created_by' => $admin->id,
            'scheduled_at' => now()->addHours(24)->addMinutes(15),
            'status' => AppointmentStatus::Scheduled,
        ]);

        $this->artisan('notifications:send-24h-reminders')
            ->expectsOutputToContain('Sent 1 24h reminder(s).')
            ->assertSuccessful();

        $this->assertSame(1, NotificationLog::query()->count());
    }

    public function test_24h_reminder_is_idempotent_on_same_day(): void
    {
        $patient = Patient::factory()->create(['phone' => '+8801712345678']);
        $admin = User::factory()->create();
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => Doctor::factory()->create()->id,
            'created_by' => $admin->id,
            'scheduled_at' => now()->addHours(24)->addMinutes(15),
            'status' => AppointmentStatus::Scheduled,
        ]);

        $this->artisan('notifications:send-24h-reminders')->assertSuccessful();
        $this->artisan('notifications:send-24h-reminders')->assertSuccessful();

        $this->assertSame(1, NotificationLog::query()->count());
    }

    public function test_24h_reminder_skipped_when_disabled(): void
    {
        $settings = app(ClinicSettings::class);
        $settings->appointment_reminder_24h_enabled = false;
        $settings->save();

        $patient = Patient::factory()->create(['phone' => '+8801712345678']);
        $admin = User::factory()->create();
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => Doctor::factory()->create()->id,
            'created_by' => $admin->id,
            'scheduled_at' => now()->addHours(24)->addMinutes(15),
            'status' => AppointmentStatus::Scheduled,
        ]);

        $this->artisan('notifications:send-24h-reminders')
            ->expectsOutputToContain('disabled in settings')
            ->assertSuccessful();

        $this->assertSame(0, NotificationLog::query()->count());
    }

    public function test_hourly_reminder_sends_within_30_to_90_min_window(): void
    {
        $patient = Patient::factory()->create(['phone' => '+8801712345678']);
        $admin = User::factory()->create();
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => Doctor::factory()->create()->id,
            'created_by' => $admin->id,
            'scheduled_at' => now()->addMinutes(60),
            'status' => AppointmentStatus::Confirmed,
        ]);

        $this->artisan('notifications:send-hourly-reminders')
            ->expectsOutputToContain('Sent 1 1h reminder(s).')
            ->assertSuccessful();

        $this->assertSame(1, NotificationLog::query()->count());
    }

    public function test_hourly_reminder_skips_appointments_outside_window(): void
    {
        $patient = Patient::factory()->create(['phone' => '+8801712345678']);
        $admin = User::factory()->create();
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => Doctor::factory()->create()->id,
            'created_by' => $admin->id,
            'scheduled_at' => now()->addHours(4),
            'status' => AppointmentStatus::Confirmed,
        ]);

        $this->artisan('notifications:send-hourly-reminders')->assertSuccessful();

        $this->assertSame(0, NotificationLog::query()->count());
    }
}
