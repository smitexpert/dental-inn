<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\CashMovementType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\CashMovement;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\PaymentService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            ChartOfAccountsSeeder::class,
            CurrencySeeder::class,
        ]);
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

    public function test_cash_payment_creates_cash_movement(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::Issued->value,
            'total' => 300,
            'paid_amount' => 0,
        ]);

        app(PaymentService::class)->record([
            'invoice_id' => $invoice->id,
            'payment_date' => '2026-08-14',
            'amount' => 300,
            'currency_code' => 'BDT',
            'exchange_rate' => 1.0,
            'method' => PaymentMethod::Cash->value,
        ], $user->id);

        $movement = CashMovement::query()->first();
        $this->assertNotNull($movement);
        $this->assertSame('BDT', $movement->currency_code);
        $this->assertSame(CashMovementType::CashIn, $movement->type);
        $this->assertSame('300.0000', (string) $movement->amount);
        $this->assertSame('payment', $movement->reference_type);
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
