<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SystemAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PaymentAggregatesTest extends TestCase
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

    public function test_aggregates_sum_correctly_for_unfiltered_index(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');

        // Invoice A: total 200, paid 50 → balance 150
        $invA = Invoice::factory()->create([
            'status' => InvoiceStatus::PartiallyPaid->value,
            'total' => 200,
            'paid_amount' => 50,
        ]);
        $this->makePayment($user->id, $invA->id, '2026-08-10', 50);

        // Invoice B: total 100, paid 100 → balance 0
        $invB = Invoice::factory()->create([
            'status' => InvoiceStatus::Paid->value,
            'total' => 100,
            'paid_amount' => 100,
        ]);
        $this->makePayment($user->id, $invB->id, '2026-08-12', 100);

        $this->actingAs($user)->get('/billing/payments')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Billing/Payments/Index')
                ->where('aggregates.count', 2)
                ->where('aggregates.total_collected', '150.00')
                ->where('aggregates.avg_payment', '75.00')
                ->where('aggregates.distinct_invoices', 2)
                ->where('aggregates.distinct_invoices_seen', 2)
                ->where('aggregates.outstanding', '150.00')
                ->etc(),
        );
    }

    public function test_aggregates_filter_by_method(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');

        $invA = Invoice::factory()->create([
            'status' => InvoiceStatus::Issued->value,
            'total' => 200,
            'paid_amount' => 0,
        ]);
        $this->makePayment($user->id, $invA->id, '2026-08-10', 80, PaymentMethod::Cash);

        $invB = Invoice::factory()->create([
            'status' => InvoiceStatus::Issued->value,
            'total' => 200,
            'paid_amount' => 0,
        ]);
        $this->makePayment($user->id, $invB->id, '2026-08-11', 120, PaymentMethod::BankTransfer);

        // Filter to Cash only — only invA's payment is in scope; its open
        // balance is the untouched invoice total (200 - 0).
        $this->actingAs($user)->get('/billing/payments?method=cash')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Billing/Payments/Index')
                ->where('aggregates.count', 1)
                ->where('aggregates.total_collected', '80.00')
                ->where('aggregates.distinct_invoices_seen', 1)
                ->where('aggregates.outstanding', '200.00')
                ->etc(),
        );
    }

    public function test_aggregates_filter_by_date_range(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');

        $invA = Invoice::factory()->create([
            'status' => InvoiceStatus::Issued->value,
            'total' => 200,
            'paid_amount' => 0,
        ]);
        $this->makePayment($user->id, $invA->id, '2026-07-01', 50);

        $invB = Invoice::factory()->create([
            'status' => InvoiceStatus::Issued->value,
            'total' => 200,
            'paid_amount' => 0,
        ]);
        $this->makePayment($user->id, $invB->id, '2026-08-15', 70);

        // Window covers August only — only invB's payment passes; its balance
        // is the untouched invoice total (200 - 0).
        $this->actingAs($user)->get('/billing/payments?date_from=2026-08-01&date_to=2026-08-31')
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Billing/Payments/Index')
                    ->where('aggregates.count', 1)
                    ->where('aggregates.total_collected', '70.00')
                    ->where('aggregates.distinct_invoices_seen', 1)
                    ->where('aggregates.outstanding', '200.00')
                    ->etc(),
            );
    }

    public function test_receipt_exposes_invoice_paid_and_balance(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::PartiallyPaid->value,
            'total' => 200,
            'paid_amount' => 75,
        ]);
        $payment = $this->makePayment($user->id, $invoice->id, '2026-08-14', 75);

        $this->actingAs($user)
            ->get("/billing/payments/{$payment->id}/receipt")
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Billing/Payments/Receipt')
                    ->where('payment.invoice.total', '200.00')
                    ->where('payment.invoice.paid_amount', '75.00')
                    ->where('payment.invoice.balance_due', '125.00')
                    ->etc(),
            );
    }

    private function makePayment(int $userId, int $invoiceId, string $date, float $amount, PaymentMethod $method = PaymentMethod::Cash): Payment
    {
        return Payment::create([
            'invoice_id' => $invoiceId,
            'payment_date' => $date,
            'amount' => $amount,
            'currency_code' => 'BDT',
            'exchange_rate' => 1.0,
            'method' => $method->value,
            'received_by' => $userId,
            'created_at' => now(),
        ]);
    }
}
