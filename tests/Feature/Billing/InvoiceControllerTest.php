<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_admin_can_create_draft_invoice(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->post('/billing/invoices', [
            'invoice_date' => '2026-08-14',
            'due_date' => '2026-09-14',
            'tax_rate' => 5,
            'discount_type' => DiscountType::None->value,
            'discount_value' => 0,
            'items' => [
                ['description' => 'Cleaning', 'quantity' => 1, 'unit_price' => 100],
                ['description' => 'X-ray', 'quantity' => 2, 'unit_price' => 50],
            ],
        ]);

        $response->assertRedirect();
        $this->assertSame(1, Invoice::count());
        $invoice = Invoice::query()->first();
        $this->assertSame(InvoiceStatus::Draft->value, $invoice->status->value);
        $this->assertSame(2, $invoice->items()->count());
    }

    public function test_admin_can_issue_draft_invoice(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::Draft->value,
            'total' => 100,
        ]);

        $response = $this->actingAs($admin)->post("/billing/invoices/{$invoice->id}/issue");
        $response->assertRedirect();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Issued->value, $invoice->status->value);
        $this->assertNotEmpty($invoice->invoice_number);
        $this->assertSame(1, LedgerEntry::query()->where('reference_type', 'invoice')->count());
    }

    public function test_receptionist_can_create_invoice(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('receptionist');

        $this->actingAs($user)
            ->post('/billing/invoices', [
                'invoice_date' => '2026-08-14',
                'due_date' => '2026-09-14',
                'tax_rate' => 0,
                'discount_type' => 'none',
                'discount_value' => 0,
                'items' => [['description' => 'Cleaning', 'quantity' => 1, 'unit_price' => 100]],
            ])
            ->assertRedirect();
    }

    public function test_receptionist_cannot_view_reports(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('receptionist');

        $this->actingAs($user)->get('/reports/ledger')->assertForbidden();
    }

    public function test_view_invoices_route_works(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');

        $this->actingAs($user)->get('/billing/invoices')->assertOk();
    }
}
