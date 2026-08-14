<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AppointmentCreated;
use App\Settings\ClinicSettings;
use App\Services\NotificationService;

class SendAppointmentCreatedNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly ClinicSettings $settings,
    ) {}

    public function handle(AppointmentCreated $event): void
    {
        $appointment = $event->appointment;
        $patient = $appointment->patient;
        $doctor = $appointment->doctor;

        if ($patient === null) {
            return;
        }

        $channel = $this->settings->notification_default_channel;

        $recipients = $this->resolveRecipients($channel, $patient->phone, $patient->email);

        if ($recipients === []) {
            return;
        }

        $this->notifications->send(
            templateKey: 'appointment-confirmation',
            channelValue: $channel,
            recipients: $recipients,
            variables: [
                'patient_name' => $patient->name,
                'doctor_name' => $doctor?->name ?? 'our clinic',
                'scheduled_at' => $appointment->scheduled_at?->format('Y-m-d H:i'),
                'clinic_name' => $this->settings->clinic_name,
            ],
            reference: $appointment,
        );
    }

    /**
     * @return array<int, string>
     */
    private function resolveRecipients(string $channel, ?string $phone, ?string $email): array
    {
        return match ($channel) {
            'email' => $email !== null && $email !== '' ? [$email] : [],
            'sms', 'whatsapp' => $phone !== null && $phone !== '' ? [$phone] : [],
            default => [],
        };
    }
}
