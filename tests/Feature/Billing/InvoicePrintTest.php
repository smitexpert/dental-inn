<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Coverage for the dedicated invoice print route — the page that the
 * clinic opens when printing an invoice onto A4 stationery. The print
 * page intentionally does NOT render the dashboard layout; these tests
 * assert the Inertia component, the serialized invoice payload, and the
 * clinic currency block the page needs to render amounts correctly.
 */
class InvoicePrintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(SettingsSeeder::class);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-invoices', 'guard_name' => 'web']));
    }

    public function test_print_route_renders_inertia_with_invoice_and_currency(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $invoice = Invoice::factory()->create();

        $response = $this->actingAs($admin)->get("/billing/invoices/{$invoice->id}/print");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Billing/Invoices/Print')
            ->where('invoice.id', $invoice->id)
            ->where('invoice.invoice_number', $invoice->invoice_number)
            ->has('currency.symbol')
            ->has('currency.code')
            ->has('currency.decimals')
            ->has('currency.position')
        );
    }

    public function test_print_route_requires_view_invoices_permission(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = Invoice::factory()->create();

        $response = $this->actingAs($user)->get("/billing/invoices/{$invoice->id}/print");

        $response->assertForbidden();
    }

    public function test_print_route_404s_for_missing_invoice(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->get('/billing/invoices/999999/print');

        $response->assertNotFound();
    }

    public function test_print_route_serializes_items_payments_and_notes(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $invoice = Invoice::factory()->create(['notes' => 'Thank you for your visit.']);

        // InvoiceItem and Payment don't have HasFactory; create directly.
        $invoice->items()->createMany([
            ['description' => 'Cleaning', 'quantity' => 1, 'unit_price' => 100, 'line_total' => 100, 'sort_order' => 1],
            ['description' => 'X-ray', 'quantity' => 2, 'unit_price' => 50, 'line_total' => 100, 'sort_order' => 2],
        ]);
        $invoice->payments()->create([
            'payment_date' => now()->toDateString(),
            'amount' => 50,
            'currency_code' => 'BDT',
            'exchange_rate' => 1,
            'method' => PaymentMethod::Cash,
            'reference' => null,
            'notes' => null,
            'received_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get("/billing/invoices/{$invoice->id}/print");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('invoice.notes', 'Thank you for your visit.')
            ->has('invoice.items', 2)
            ->has('invoice.payments', 1)
        );
    }
}
