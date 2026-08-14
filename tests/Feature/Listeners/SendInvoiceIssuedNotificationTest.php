<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Enums\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Patient;
use App\Models\User;
use App\Repositories\Contracts\InvoiceRepository;
use App\Settings\ClinicSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendInvoiceIssuedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_invoice_notification_when_enabled(): void
    {
        NotificationTemplate::factory()->create([
            'key' => 'invoice-issued',
            'channel' => NotificationChannel::Sms,
            'body' => 'Invoice {{ invoice_number }} total {{ total }}',
            'is_active' => true,
        ]);

        $settings = app(ClinicSettings::class);
        $settings->notification_default_channel = 'sms';
        $settings->invoice_notification_enabled = true;
        $settings->save();

        $patient = Patient::factory()->create(['phone' => '+8801712345678']);
        $admin = User::factory()->create();

        // Dispatch via the repository path (which is the prod dispatch site)
        $repo = app(InvoiceRepository::class);
        $invoice = $repo->create([
            'invoice_number' => 'INV-'.uniqid(),
            'patient_id' => $patient->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'subtotal' => 1000,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'discount_type' => 'none',
            'discount_value' => 0,
            'discount_amount' => 0,
            'total' => 1000,
            'paid_amount' => 0,
            'status' => 'issued',
            'created_by' => $admin->id,
        ]);

        $log = NotificationLog::query()->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('sms', $log->channel->value);
        $this->assertStringContainsString('Invoice ', $log->body_rendered);
    }

    public function test_skips_when_invoice_notification_disabled(): void
    {
        NotificationTemplate::factory()->create([
            'key' => 'invoice-issued',
            'channel' => NotificationChannel::Sms,
            'body' => 'Invoice',
            'is_active' => true,
        ]);

        $settings = app(ClinicSettings::class);
        $settings->notification_default_channel = 'sms';
        $settings->invoice_notification_enabled = false;
        $settings->save();

        $patient = Patient::factory()->create(['phone' => '+8801712345678']);
        $admin = User::factory()->create();

        $repo = app(InvoiceRepository::class);
        $repo->create([
            'invoice_number' => 'INV-X',
            'patient_id' => $patient->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'subtotal' => 100,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'discount_type' => 'none',
            'discount_value' => 0,
            'discount_amount' => 0,
            'total' => 100,
            'paid_amount' => 0,
            'status' => 'issued',
            'created_by' => $admin->id,
        ]);

        $this->assertSame(0, NotificationLog::query()->count());
    }
}
