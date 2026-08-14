<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
    }

    public function test_record_updates_invoice_and_status(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::Issued->value,
            'total' => 200,
            'paid_amount' => 0,
        ]);

        $payment = app(PaymentService::class)->record([
            'invoice_id' => $invoice->id,
            'payment_date' => '2026-08-14',
            'amount' => 200,
            'currency_code' => 'BDT',
            'exchange_rate' => 1.0,
            'method' => PaymentMethod::Cash->value,
        ], $user->id);

        $invoice->refresh();
        $this->assertSame('200.00', (string) $invoice->paid_amount);
        $this->assertSame(InvoiceStatus::Paid->value, $invoice->status->value);
        $this->assertSame(1, LedgerEntry::query()->where('reference_type', 'payment')->count());
    }

    public function test_partial_payment_sets_partially_paid(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::Issued->value,
            'total' => 200,
            'paid_amount' => 0,
        ]);

        app(PaymentService::class)->record([
            'invoice_id' => $invoice->id,
            'payment_date' => '2026-08-14',
            'amount' => 50,
            'currency_code' => 'BDT',
            'exchange_rate' => 1.0,
            'method' => PaymentMethod::Cash->value,
        ], $user->id);

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::PartiallyPaid->value, $invoice->status->value);
        $this->assertSame('50.00', (string) $invoice->paid_amount);
    }

    public function test_payment_exceeding_total_throws(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::Issued->value,
            'total' => 100,
            'paid_amount' => 0,
        ]);

        $this->expectException(RuntimeException::class);
        app(PaymentService::class)->record([
            'invoice_id' => $invoice->id,
            'payment_date' => '2026-08-14',
            'amount' => 150,
            'currency_code' => 'BDT',
            'exchange_rate' => 1.0,
            'method' => PaymentMethod::Cash->value,
        ], $user->id);
    }
}
