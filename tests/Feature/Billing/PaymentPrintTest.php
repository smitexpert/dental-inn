<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Coverage for the dedicated payment receipt print route — the page
 * that opens when printing a receipt onto A4 stationery. The print
 * page intentionally does NOT render the dashboard layout; these tests
 * assert the Inertia component, the serialized payment payload, and
 * the clinic currency block the page needs to render amounts.
 */
class PaymentPrintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(SettingsSeeder::class);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-payments', 'guard_name' => 'web']));
    }

    private function makePayment(User $receiver, array $overrides = []): Payment
    {
        $invoice = $overrides['invoice'] ?? Invoice::factory()->create();

        return Payment::create([
            'invoice_id' => $invoice->id,
            'payment_date' => now()->toDateString(),
            'amount' => 100,
            'currency_code' => 'BDT',
            'exchange_rate' => 1,
            'method' => PaymentMethod::Cash,
            'reference' => null,
            'notes' => null,
            'received_by' => $receiver->id,
            ...array_diff_key($overrides, ['invoice' => null]),
        ]);
    }

    public function test_print_route_renders_inertia_with_payment_and_currency(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $payment = $this->makePayment($admin);

        $response = $this->actingAs($admin)->get("/billing/payments/{$payment->id}/receipt/print");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Billing/Payments/Print')
            ->where('payment.id', $payment->id)
            ->has('currency.symbol')
            ->has('currency.code')
            ->has('currency.decimals')
            ->has('currency.position')
        );
    }

    public function test_print_route_requires_view_payments_permission(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $payment = $this->makePayment($user);

        $response = $this->actingAs($user)->get("/billing/payments/{$payment->id}/receipt/print");

        $response->assertForbidden();
    }

    public function test_print_route_404s_for_missing_payment(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->get('/billing/payments/999999/receipt/print');

        $response->assertNotFound();
    }

    public function test_print_route_serializes_full_payment_payload(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create([
            'patient_code' => 'P-12345',
            'name' => 'Jane Doe',
        ]);
        $invoice = Invoice::factory()->create(['patient_id' => $patient->id]);

        $payment = $this->makePayment($admin, [
            'invoice' => $invoice,
            'amount' => 250,
            'method' => PaymentMethod::BankTransfer,
            'reference' => 'TRX-9988',
            'notes' => 'Paid via mobile banking',
        ]);

        $response = $this->actingAs($admin)->get("/billing/payments/{$payment->id}/receipt/print");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('payment.amount', '250.00')
            ->where('payment.method', 'bank_transfer')
            ->where('payment.method_label', 'Bank transfer')
            ->where('payment.reference', 'TRX-9988')
            ->where('payment.notes', 'Paid via mobile banking')
            ->where('payment.receiver', $admin->name)
            ->where('payment.invoice.invoice_number', $invoice->invoice_number)
            ->where('payment.invoice.patient.patient_code', 'P-12345')
            ->where('payment.invoice.patient.name', 'Jane Doe')
            ->has('payment.invoice.show_url')
        );
    }
}
