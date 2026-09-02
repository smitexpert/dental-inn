<?php

declare(strict_types=1);

namespace Tests\Feature\Bank;

use App\Enums\BankAccountType;
use App\Enums\CashMovementType;
use App\Models\BankAccount;
use App\Models\CashMovement;
use App\Models\User;
use App\Services\BankAccountService;
use Database\Seeders\SystemAccountsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AccountServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccountsSeeder::class);
    }

    private function service(): BankAccountService
    {
        return $this->app->make(BankAccountService::class);
    }

    public function test_creates_cash_account_with_zero_opening_balance(): void
    {
        $user = User::factory()->create();
        $account = $this->service()->create([
            'name' => 'Petty Cash',
            'type' => BankAccountType::Cash->value,
        ], $user->id);

        $this->assertSame('Petty Cash', $account->name);
        $this->assertSame('0.0000', (string) $account->balance);
        $this->assertSame(0, CashMovement::query()->where('account_id', $account->id)->count());
    }

    public function test_creating_bank_account_without_account_number_throws(): void
    {
        $user = User::factory()->create();

        $this->expectException(RuntimeException::class);
        $this->service()->create([
            'name' => 'Acme',
            'type' => BankAccountType::Bank->value,
        ], $user->id);
    }

    public function test_opening_balance_creates_transfer_pair_against_external(): void
    {
        $user = User::factory()->create();
        $account = $this->service()->create([
            'name' => 'Acme Bank',
            'type' => BankAccountType::Bank->value,
            'bank_name' => 'Acme',
            'account_name' => 'Clinic',
            'account_number' => '123456',
            'opening_balance' => 5000,
        ], $user->id);

        $this->assertSame('5000.0000', (string) $account->balance);

        $movements = CashMovement::query()->where('account_id', $account->id)->get();
        $this->assertCount(1, $movements);
        $this->assertSame(CashMovementType::TransferIn, $movements->first()->type);
        $this->assertSame('5000.0000', (string) $movements->first()->amount);

        $external = BankAccount::query()->where('name', 'External Adjustment')->firstOrFail();
        $this->assertSame('-5000.0000', (string) $external->fresh()->balance);
    }

    public function test_unique_account_number_per_bank(): void
    {
        $user = User::factory()->create();
        $this->service()->create([
            'name' => 'Acme A',
            'type' => BankAccountType::Bank->value,
            'bank_name' => 'Acme',
            'account_name' => 'A',
            'account_number' => '111',
        ], $user->id);

        $this->expectException(QueryException::class);
        $this->service()->create([
            'name' => 'Acme B',
            'type' => BankAccountType::Bank->value,
            'bank_name' => 'Acme',
            'account_name' => 'B',
            'account_number' => '111',
        ], $user->id);
    }

    public function test_system_account_cannot_be_modified(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service()->update(
            BankAccount::query()->where('name', 'Main Cash')->firstOrFail(),
            ['name' => 'Renamed'],
        );
    }
}
