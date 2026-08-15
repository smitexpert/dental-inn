<?php

declare(strict_types=1);

namespace Tests\Feature\Bank;

use App\Models\BankAccount;
use App\Models\User;
use Database\Seeders\SystemAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReconciliationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccountsSeeder::class);
        Permission::findOrCreate('reconcile-accounts', 'web');
    }

    public function test_run_endpoint_reconciles(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('reconcile-accounts');
        $account = BankAccount::factory()->cash()->create(['balance' => 1000]);

        $this->actingAs($user)->post(route('bank.reconcile.run'), [
            'account_id' => $account->id,
            'counted_balance' => 1000,
        ])->assertRedirect(route('bank.reconcile.index'));

        $this->assertNotNull($account->fresh()->last_reconciled_at);
    }
}
