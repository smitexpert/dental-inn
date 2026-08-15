<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SystemAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed([
            ChartOfAccountsSeeder::class,
            SystemAccountsSeeder::class,
        ]);
    }

    public function test_payment_records_marks_partially_paid(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::Issued->value,
            'total' => 200,
            'paid_amount' => 0,
        ]);

        $response = $this->actingAs($user)->post('/billing/payments', [
            'invoice_id' => $invoice->id,
            'payment_date' => '2026-08-14',
            'amount' => 75,
            'currency_code' => 'BDT',
            'exchange_rate' => 1.0,
            'method' => PaymentMethod::Cash->value,
        ]);

        $response->assertRedirect();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::PartiallyPaid->value, $invoice->status->value);
        $this->assertSame(1, Payment::count());
        $this->assertSame(1, LedgerEntry::query()->where('reference_type', 'payment')->count());
    }

    public function test_payment_exceeding_total_rejected(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::Issued->value,
            'total' => 100,
            'paid_amount' => 0,
        ]);

        $response = $this->actingAs($user)->post('/billing/payments', [
            'invoice_id' => $invoice->id,
            'payment_date' => '2026-08-14',
            'amount' => 150,
            'currency_code' => 'BDT',
            'exchange_rate' => 1.0,
            'method' => PaymentMethod::Cash->value,
        ]);

        $response->assertSessionHasErrors();
        $this->assertSame(0, Payment::count());
    }

    public function test_payment_receipt_route_works(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::Issued->value,
            'total' => 100,
        ]);
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'payment_date' => '2026-08-14',
            'amount' => 50,
            'currency_code' => 'BDT',
            'exchange_rate' => 1.0,
            'method' => PaymentMethod::Cash->value,
            'received_by' => $user->id,
            'created_at' => now(),
        ]);

        $this->actingAs($user)->get("/billing/payments/{$payment->id}/receipt")->assertOk();
    }
}
