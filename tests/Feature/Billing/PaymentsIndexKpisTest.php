<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentsIndexKpisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_index_returns_kpis(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::Issued->value,
            'total' => 200,
            'paid_amount' => 0,
        ]);

        Payment::query()->create([
            'invoice_id' => $invoice->id,
            'amount' => 100,
            'currency_code' => 'BDT',
            'exchange_rate' => 1.0,
            'method' => PaymentMethod::Cash->value,
            'payment_date' => today(),
            'received_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get('/billing/payments');
        $response->assertOk();

        $kpis = $response->viewData('page')['props']['kpis'] ?? [];
        $this->assertCount(4, $kpis);
        $this->assertSame('today', $kpis[0]['id']);
        $this->assertSame('currency', $kpis[0]['format']);
        $this->assertSame('month', $kpis[1]['id']);
        $this->assertSame('count', $kpis[3]['id']);
    }
}
