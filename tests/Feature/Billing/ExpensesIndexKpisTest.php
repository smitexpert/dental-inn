<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\ExpenseStatus;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpensesIndexKpisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);
        $this->seed(ExpenseCategorySeeder::class);
    }

    public function test_index_returns_kpis(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->get('/billing/expenses');
        $response->assertOk();

        $kpis = $response->viewData('page')['props']['kpis'] ?? [];
        $this->assertCount(4, $kpis);
        $this->assertSame('pending', $kpis[0]['id']);
        $this->assertSame('number', $kpis[0]['format']);
        $this->assertSame('month', $kpis[1]['id']);
        $this->assertSame('currency', $kpis[1]['format']);
        $this->assertSame('top', $kpis[3]['id']);
        $this->assertSame('—', $kpis[3]['value']);
    }
}
