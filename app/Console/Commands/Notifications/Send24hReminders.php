<?php

declare(strict_types=1);

namespace App\Console\Commands\Notifications;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Settings\ClinicSettings;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class Send24hReminders extends Command
{
    protected $signature = 'notifications:send-24h-reminders {--now= : ISO datetime to evaluate against}';

    protected $description = 'Send 24h appointment reminders (idempotent via notification_logs).';

    public function __construct(
        private readonly NotificationService $notifications,
        private readonly ClinicSettings $settings,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->settings->appointment_reminder_24h_enabled) {
            $this->info('24h appointment reminders are disabled in settings.');

            return self::SUCCESS;
        }

        $now = $this->parseNow();

        // Window: scheduled_at in [now+23h, now+25h].
        $start = $now->copy()->addHours(23);
        $end = $now->copy()->addHours(25);

        $channel = $this->settings->notification_default_channel;

        $appointments = Appointment::query()
            ->with(['patient', 'doctor'])
            ->whereIn('status', [
                AppointmentStatus::Scheduled->value,
                AppointmentStatus::Confirmed->value,
            ])
            ->whereBetween('scheduled_at', [$start, $end])
            ->get();

        $sent = 0;

        foreach ($appointments as $appointment) {
            $patient = $appointment->patient;
            if ($patient === null) {
                continue;
            }

            $recipients = $this->recipients($channel, $patient->phone, $patient->email);
            if ($recipients === []) {
                continue;
            }

            $results = $this->notifications->send(
                templateKey: 'appointment-reminder-24h',
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

            foreach ($results as $r) {
                if (($r['logged'] ?? false) && ($r['sent'] ?? false)) {
                    $sent++;
                }
            }
        }

        $this->info("Sent {$sent} 24h reminder(s).");

        return self::SUCCESS;
    }

    private function parseNow(): Carbon
    {
        $opt = (string) ($this->option('now') ?: '');

        return $opt !== '' ? Carbon::parse($opt) : Carbon::now();
    }

    /**
     * @return array<int, string>
     */
    private function recipients(string $channel, ?string $phone, ?string $email): array
    {
        return match ($channel) {
            'email' => $email !== null && $email !== '' ? [$email] : [],
            'sms', 'whatsapp' => $phone !== null && $phone !== '' ? [$phone] : [],
            default => [],
        };
    }
}
