<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicesIndexKpisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_index_returns_kpis(): void
    {
        Invoice::factory()->create([
            'status' => InvoiceStatus::Issued->value,
            'total' => 500,
            'paid_amount' => 0,
        ]);

        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->get('/billing/invoices');
        $response->assertOk();

        $kpis = $response->viewData('page')['props']['kpis'] ?? [];
        $this->assertCount(4, $kpis);
        $this->assertSame('outstanding', $kpis[0]['id']);
        $this->assertSame('currency', $kpis[0]['format']);
        $this->assertSame('overdue', $kpis[2]['id']);
        $this->assertSame('issued_month', $kpis[3]['id']);
    }
}
