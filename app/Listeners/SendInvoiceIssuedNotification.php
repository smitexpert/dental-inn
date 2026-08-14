<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\InvoiceIssued;
use App\Settings\ClinicSettings;
use App\Services\NotificationService;

class SendInvoiceIssuedNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly ClinicSettings $settings,
    ) {}

    public function handle(InvoiceIssued $event): void
    {
        if (! $this->settings->invoice_notification_enabled) {
            return;
        }

        $invoice = $event->invoice;
        $patient = $invoice->patient;

        if ($patient === null) {
            return;
        }

        $channel = $this->settings->notification_default_channel;

        $recipients = $this->channelRecipients($channel, $patient->phone, $patient->email);
        if ($recipients === []) {
            return;
        }

        $this->notifications->send(
            templateKey: 'invoice-issued',
            channelValue: $channel,
            recipients: $recipients,
            variables: [
                'patient_name' => $patient->name,
                'invoice_number' => $invoice->invoice_number,
                'total' => number_format((float) $invoice->total, 2),
                'due_date' => $invoice->due_date?->format('Y-m-d'),
                'clinic_name' => $this->settings->clinic_name,
            ],
            reference: $invoice,
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
