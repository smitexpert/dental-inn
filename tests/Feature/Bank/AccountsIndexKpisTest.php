<?php

declare(strict_types=1);

namespace Tests\Feature\Bank;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountsIndexKpisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-accounts', 'guard_name' => 'web']));
    }

    public function test_index_returns_kpis(): void
    {
        BankAccount::factory()->create(['is_active' => true, 'balance' => 1000]);
        BankAccount::factory()->create(['is_active' => true, 'balance' => 500, 'last_reconciled_at' => today()]);

        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->get('/bank/accounts');
        $response->assertOk();

        $kpis = $response->viewData('page')['props']['kpis'] ?? [];
        $this->assertCount(4, $kpis);
        $this->assertSame('balance', $kpis[0]['id']);
        $this->assertSame('currency', $kpis[0]['format']);
        $this->assertSame('active', $kpis[1]['id']);
        $this->assertSame('recon', $kpis[2]['id']);
        $this->assertSame('pending', $kpis[3]['id']);
    }
}
