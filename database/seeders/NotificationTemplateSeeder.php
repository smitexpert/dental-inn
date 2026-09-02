<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\NotificationChannel;
use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // SMS
            [
                'key' => 'appointment-confirmation',
                'channel' => NotificationChannel::Sms,
                'subject' => null,
                'body' => 'Hello {{ patient_name }}, your appointment with Dr. {{ doctor_name }} is confirmed for {{ scheduled_at }}. Reply YES to confirm.',
                'is_active' => true,
                'locale' => 'en',
            ],
            [
                'key' => 'appointment-reminder-24h',
                'channel' => NotificationChannel::Sms,
                'subject' => null,
                'body' => 'Reminder: {{ patient_name }}, you have an appointment tomorrow at {{ scheduled_at }} with Dr. {{ doctor_name }}.',
                'is_active' => true,
                'locale' => 'en',
            ],
            [
                'key' => 'appointment-reminder-1h',
                'channel' => NotificationChannel::Sms,
                'subject' => null,
                'body' => 'Reminder: {{ patient_name }}, your appointment with Dr. {{ doctor_name }} is in 1 hour ({{ scheduled_at }}).',
                'is_active' => true,
                'locale' => 'en',
            ],
            [
                'key' => 'appointment-cancelled',
                'channel' => NotificationChannel::Sms,
                'subject' => null,
                'body' => 'Hello {{ patient_name }}, your appointment scheduled for {{ scheduled_at }} has been cancelled. Please call us to reschedule.',
                'is_active' => true,
                'locale' => 'en',
            ],
            [
                'key' => 'invoice-issued',
                'channel' => NotificationChannel::Sms,
                'subject' => null,
                'body' => 'Hello {{ patient_name }}, invoice {{ invoice_number }} for {{ total }} is ready. Due: {{ due_date }}.',
                'is_active' => true,
                'locale' => 'en',
            ],
            [
                'key' => 'payment-received',
                'channel' => NotificationChannel::Sms,
                'subject' => null,
                'body' => 'Hello {{ patient_name }}, we received your payment of {{ amount }} for invoice {{ invoice_number }}. Balance: {{ balance }}.',
                'is_active' => true,
                'locale' => 'en',
            ],

            // WhatsApp
            [
                'key' => 'appointment-confirmation',
                'channel' => NotificationChannel::Whatsapp,
                'subject' => null,
                'body' => '*Appointment Confirmed*\n\nHello {{ patient_name }},\n\nYour appointment with *Dr. {{ doctor_name }}* is confirmed for {{ scheduled_at }}.\n\nPlease reply YES to confirm.',
                'is_active' => true,
                'locale' => 'en',
            ],

            // Email
            [
                'key' => 'appointment-confirmation',
                'channel' => NotificationChannel::Email,
                'subject' => 'Your appointment at {{ clinic_name }} is confirmed',
                'body' => "Hello {{ patient_name }},\n\nYour appointment with Dr. {{ doctor_name }} is confirmed for {{ scheduled_at }}.\n\nIf you need to reschedule, please call us.\n\nThank you,\n{{ clinic_name }}",
                'is_active' => true,
                'locale' => 'en',
            ],
            [
                'key' => 'invoice-issued',
                'channel' => NotificationChannel::Email,
                'subject' => 'Invoice {{ invoice_number }} from {{ clinic_name }}',
                'body' => "Hello {{ patient_name }},\n\nPlease find attached invoice {{ invoice_number }} for {{ total }}.\nDue date: {{ due_date }}.\n\nThank you,\n{{ clinic_name }}",
                'is_active' => true,
                'locale' => 'en',
            ],
            [
                'key' => 'payment-received',
                'channel' => NotificationChannel::Email,
                'subject' => 'Payment received for invoice {{ invoice_number }}',
                'body' => "Hello {{ patient_name }},\n\nWe have received your payment of {{ amount }} for invoice {{ invoice_number }}.\nRemaining balance: {{ balance }}.\n\nThank you,\n{{ clinic_name }}",
                'is_active' => true,
                'locale' => 'en',
            ],
        ];

        foreach ($templates as $row) {
            NotificationTemplate::query()->updateOrCreate(
                [
                    'key' => $row['key'],
                    'channel' => $row['channel']->value,
                    'locale' => $row['locale'],
                ],
                [
                    'subject' => $row['subject'],
                    'body' => $row['body'],
                    'is_active' => $row['is_active'],
                ],
            );
        }
    }
}
