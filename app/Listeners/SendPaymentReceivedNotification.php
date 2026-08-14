<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Services\NotificationService;
use App\Settings\ClinicSettings;

class SendPaymentReceivedNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly ClinicSettings $settings,
    ) {}

    public function handle(PaymentReceived $event): void
    {
        if (! $this->settings->payment_notification_enabled) {
            return;
        }

        $payment = $event->payment;
        $invoice = $payment->invoice;
        $patient = $invoice?->patient;

        if ($patient === null) {
            return;
        }

        $channel = $this->settings->notification_default_channel;

        $recipients = $this->channelRecipients($channel, $patient->phone, $patient->email);
        if ($recipients === []) {
            return;
        }

        $balance = (float) $invoice->total - (float) $invoice->paid_amount;

        $this->notifications->send(
            templateKey: 'payment-received',
            channelValue: $channel,
            recipients: $recipients,
            variables: [
                'patient_name' => $patient->name,
                'invoice_number' => $invoice->invoice_number,
                'amount' => number_format((float) $payment->amount, 2),
                'balance' => number_format($balance, 2),
                'clinic_name' => $this->settings->clinic_name,
            ],
            reference: $payment,
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
