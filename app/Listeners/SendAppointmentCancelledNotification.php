<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AppointmentCancelled;
use App\Settings\ClinicSettings;
use App\Services\NotificationService;

class SendAppointmentCancelledNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly ClinicSettings $settings,
    ) {}

    public function handle(AppointmentCancelled $event): void
    {
        $appointment = $event->appointment;
        $patient = $appointment->patient;

        if ($patient === null) {
            return;
        }

        $channel = $this->settings->notification_default_channel;

        $recipients = $this->channelRecipients($channel, $patient->phone, $patient->email);
        if ($recipients === []) {
            return;
        }

        $this->notifications->send(
            templateKey: 'appointment-cancelled',
            channelValue: $channel,
            recipients: $recipients,
            variables: [
                'patient_name' => $patient->name,
                'doctor_name' => $appointment->doctor?->name ?? 'our clinic',
                'scheduled_at' => $appointment->scheduled_at?->format('Y-m-d H:i'),
                'clinic_name' => $this->settings->clinic_name,
            ],
            reference: $appointment,
        );
    }

    /**
     * @return array<int, string>
     */
    private function channelRecipients(string $channel, ?string $phone, ?string $email): array
    {
        return match ($channel) {
            'email' => $email !== null && $email !== '' ? [$email] : [],
            'sms', 'whatsapp' => $phone !== null && $phone !== '' ? [$phone] : [],
            default => [],
        };
    }
}
