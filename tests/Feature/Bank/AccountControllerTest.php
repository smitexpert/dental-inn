<?php

declare(strict_types=1);

namespace Tests\Feature\Bank;

use App\Models\BankAccount;
use App\Models\User;
use Database\Seeders\SystemAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccountsSeeder::class);
        foreach (['view-accounts', 'manage-accounts'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
    }

    public function test_index_lists_accounts(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view-accounts');
        BankAccount::factory()->count(3)->create();

        $this->actingAs($user)
            ->get(route('bank.accounts.index'))
            ->assertOk();
    }

    public function test_store_persists_a_bank_account(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['view-accounts', 'manage-accounts']);

        $this->actingAs($user)->post(route('bank.accounts.store'), [
            'name' => 'Test Bank',
            'type' => 'bank',
            'bank_name' => 'Test',
            'account_name' => 'Clinic',
            'account_number' => '999',
            'opening_balance' => 100,
        ])->assertRedirect(route('bank.accounts.index'));

        $this->assertDatabaseHas('bank_accounts', ['name' => 'Test Bank', 'balance' => '100.0000']);
    }
}
