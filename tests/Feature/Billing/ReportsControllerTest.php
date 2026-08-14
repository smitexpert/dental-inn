<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_ledger_route_returns_ok_for_admin(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($user)->get('/reports/ledger')->assertOk();
    }

    public function test_income_statement_route_returns_ok(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($user)->get('/reports/income-statement')->assertOk();
    }

    public function test_balance_sheet_route_returns_ok(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($user)->get('/reports/balance-sheet')->assertOk();
    }

    public function test_ar_aging_route_returns_ok(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($user)->get('/reports/accounts-receivable')->assertOk();
    }

    public function test_doctor_can_view_reports(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('doctor');
        $this->actingAs($user)->get('/reports/ledger')->assertOk();
    }
}
