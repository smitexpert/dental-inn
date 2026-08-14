<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Enums\AppointmentStatus;
use App\Enums\NotificationChannel;
use App\Events\AppointmentCreated;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Patient;
use App\Models\User;
use App\Settings\ClinicSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendAppointmentCreatedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_confirmation_with_patient_phone(): void
    {
        NotificationTemplate::factory()->create([
            'key' => 'appointment-confirmation',
            'channel' => NotificationChannel::Sms,
            'body' => 'Hello {{ patient_name }}, with Dr. {{ doctor_name }} at {{ scheduled_at }}.',
            'is_active' => true,
        ]);

        app(ClinicSettings::class)->notification_default_channel = 'sms';
        app(ClinicSettings::class)->save();

        $patient = Patient::factory()->create([
            'phone' => '+8801712345678',
            'email' => 'patient@example.test',
        ]);

        $doctorUser = User::factory()->create(['name' => 'Dr. Bob']);
        $doctor = Doctor::factory()->create();
        $doctor->user_id = $doctorUser->id;
        $doctor->save();

        $admin = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'scheduled_at' => now()->addDay()->setTime(10, 0),
            'status' => AppointmentStatus::Scheduled,
            'created_by' => $admin->id,
        ]);

        event(new AppointmentCreated($appointment));

        $log = NotificationLog::query()->first();
        $this->assertNotNull($log, 'expected a notification_log row to be created');
        $this->assertSame('+8801712345678', $log->recipient);
        $this->assertSame('sms', $log->channel->value);
        $this->assertStringContainsString('Dr. Bob', $log->body_rendered);
        $this->assertStringContainsString($patient->name, $log->body_rendered);
        $this->assertSame(Appointment::class, $log->reference_type);
        $this->assertSame($appointment->id, $log->reference_id);
    }

    public function test_does_nothing_when_patient_has_no_relevant_contact(): void
    {
        NotificationTemplate::factory()->create([
            'key' => 'appointment-confirmation',
            'channel' => NotificationChannel::Sms,
            'body' => 'Hi',
            'is_active' => true,
        ]);

        app(ClinicSettings::class)->notification_default_channel = 'sms';
        app(ClinicSettings::class)->save();

        $patient = Patient::factory()->create(['phone' => null, 'email' => null]);

        $admin = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'scheduled_at' => now()->addDay(),
            'created_by' => $admin->id,
        ]);

        event(new AppointmentCreated($appointment));

        $this->assertSame(0, NotificationLog::query()->count());
    }

    public function test_silently_skips_when_no_template_seeded(): void
    {
        app(ClinicSettings::class)->notification_default_channel = 'sms';
        app(ClinicSettings::class)->save();

        $patient = Patient::factory()->create(['phone' => '+8801712345678']);
        $admin = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'scheduled_at' => now()->addDay(),
            'created_by' => $admin->id,
        ]);

        event(new AppointmentCreated($appointment));

        $this->assertSame(0, NotificationLog::query()->count());
    }
}
