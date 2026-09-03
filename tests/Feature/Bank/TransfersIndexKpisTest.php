<?php

declare(strict_types=1);

namespace Tests\Feature\Bank;

use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TransfersIndexKpisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-transfers', 'guard_name' => 'web']));
    }

    public function test_index_returns_kpis(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $from = \App\Models\BankAccount::factory()->create();
        $to = \App\Models\BankAccount::factory()->create();

        Transfer::factory()->create([
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
            'amount' => 250,
            'occurred_at' => today(),
            'transfer_group_id' => 'tg-1',
            'performed_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get('/bank/transfers');
        $response->assertOk();

        $kpis = $response->viewData('page')['props']['kpis'] ?? [];
        $this->assertCount(3, $kpis);
        $this->assertSame('today', $kpis[0]['id']);
        $this->assertSame('number', $kpis[0]['format']);
        $this->assertSame('volume', $kpis[1]['id']);
        $this->assertSame('currency', $kpis[1]['format']);
        $this->assertSame('largest', $kpis[2]['id']);
    }
}
