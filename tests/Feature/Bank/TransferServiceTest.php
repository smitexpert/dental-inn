<?php

declare(strict_types=1);

namespace Tests\Feature\Bank;

use App\Enums\BankAccountType;
use App\Enums\CashMovementType;
use App\Models\BankAccount;
use App\Models\CashMovement;
use App\Models\User;
use App\Services\BankAccountService;
use App\Services\TransferService;
use Database\Seeders\SystemAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class TransferServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccountsSeeder::class);
    }

    private function setupAccounts(int $cashBalance = 1000, int $bankBalance = 0): array
    {
        $user = User::factory()->create();

        $service = $this->app->make(BankAccountService::class);

        $cash = $service->create(['name' => 'Cash', 'type' => BankAccountType::Cash->value], $user->id);
        $bank = $service->create([
            'name' => 'Acme',
            'type' => BankAccountType::Bank->value,
            'bank_name' => 'Acme',
            'account_name' => 'Clinic',
            'account_number' => '123',
            'opening_balance' => $bankBalance,
        ], $user->id);

        if ($cashBalance > 0) {
            $this->app->make(TransferService::class)->execute(
                BankAccount::query()->where('name', 'External Adjustment')->first()->id,
                $cash->id,
                (string) $cashBalance,
                null,
                'Test setup',
                null,
                $user->id,
            );
        }

        return [$user, $cash, $bank];
    }

    public function test_cash_to_bank_transfer(): void
    {
        [$user, $cash, $bank] = $this->setupAccounts(1000, 0);

        $this->app->make(TransferService::class)->execute(
            $cash->id,
            $bank->id,
            '300',
            null,
            null,
            null,
            $user->id,
        );

        $this->assertSame('700.0000', (string) $cash->fresh()->balance);
        $this->assertSame('300.0000', (string) $bank->fresh()->balance);

        $movements = CashMovement::query()->orderBy('id')->get();
        $this->assertCount(2, $movements);
        $this->assertSame(CashMovementType::TransferOut, $movements[0]->type);
        $this->assertSame(CashMovementType::TransferIn, $movements[1]->type);
        $this->assertSame($movements[0]->transfer_group_id, $movements[1]->transfer_group_id);
    }

    public function test_insufficient_balance_throws(): void
    {
        [$user, $cash, $bank] = $this->setupAccounts(100, 0);

        $this->expectException(RuntimeException::class);
        $this->app->make(TransferService::class)->execute(
            $cash->id,
            $bank->id,
            '500',
            null,
            null,
            null,
            $user->id,
        );
    }

    public function test_same_source_and_destination_rejected(): void
    {
        [$user, $cash] = $this->setupAccounts(100, 0);

        $this->expectException(RuntimeException::class);
        $this->app->make(TransferService::class)->execute(
            $cash->id,
            $cash->id,
            '10',
            null,
            null,
            null,
            $user->id,
        );
    }

    public function test_amount_must_be_positive(): void
    {
        [$user, $cash, $bank] = $this->setupAccounts(100, 0);

        $this->expectException(RuntimeException::class);
        $this->app->make(TransferService::class)->execute(
            $cash->id,
            $bank->id,
            '0',
            null,
            null,
            null,
            $user->id,
        );
    }

    public function test_balances_rollback_on_failure(): void
    {
        [$user, $cash, $bank] = $this->setupAccounts(100, 0);

        try {
            $this->app->make(TransferService::class)->execute(
                $cash->id,
                $bank->id,
                '500',
                null,
                null,
                null,
                $user->id,
            );
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame('100.0000', (string) $cash->fresh()->balance);
        $this->assertSame('0.0000', (string) $bank->fresh()->balance);
    }
}
