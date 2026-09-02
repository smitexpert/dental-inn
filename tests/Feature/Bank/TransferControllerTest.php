<?php

declare(strict_types=1);

namespace Tests\Feature\Bank;

use App\Models\BankAccount;
use App\Models\User;
use Database\Seeders\SystemAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TransferControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccountsSeeder::class);
        foreach (['view-transfers', 'record-transfers'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
    }

    public function test_store_creates_transfer(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['view-transfers', 'record-transfers']);

        $from = BankAccount::factory()->cash()->create(['balance' => 500]);
        $to = BankAccount::factory()->create();

        $this->actingAs($user)->post(route('bank.transfers.store'), [
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
            'amount' => 200,
        ])->assertRedirect(route('bank.transfers.index'));

        $this->assertSame('300.0000', (string) $from->fresh()->balance);
        $this->assertSame('200.0000', (string) $to->fresh()->balance);
    }

    public function test_store_rejects_same_source_and_destination(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['view-transfers', 'record-transfers']);
        $acc = BankAccount::factory()->cash()->create();

        $this->actingAs($user)->post(route('bank.transfers.store'), [
            'from_account_id' => $acc->id,
            'to_account_id' => $acc->id,
            'amount' => 10,
        ])->assertSessionHasErrors('to_account_id');
    }
}
