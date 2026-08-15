<?php

declare(strict_types=1);

namespace Tests\Feature\Bank;

use App\Enums\BankAccountType;
use App\Models\BankAccount;
use App\Models\CashMovement;
use App\Models\User;
use App\Services\BankAccountService;
use App\Services\ReconciliationService;
use Database\Seeders\SystemAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccountsSeeder::class);
    }

    private function makeAccount(int $opening): BankAccount
    {
        $user = User::factory()->create();
        return $this->app->make(BankAccountService::class)->create([
            'name' => 'Cash',
            'type' => BankAccountType::Cash->value,
            'opening_balance' => $opening,
        ], $user->id);
    }

    public function test_matching_count_stamps_timestamp(): void
    {
        $account = $this->makeAccount(500);
        $this->app->make(ReconciliationService::class)->run($account->id, '500', User::factory()->create()->id);

        $fresh = $account->fresh();
        $this->assertNotNull($fresh->last_reconciled_at);
        $this->assertSame('500.0000', (string) $fresh->last_reconciled_balance);
    }

    public function test_positive_variance_creates_transfer_to_account(): void
    {
        $account = $this->makeAccount(500);
        $movementsBefore = CashMovement::query()->count();

        $this->app->make(ReconciliationService::class)->run($account->id, '550', User::factory()->create()->id);

        $this->assertSame('550.0000', (string) $account->fresh()->balance);
        $this->assertSame($movementsBefore + 2, CashMovement::query()->count());
    }

    public function test_negative_variance_creates_transfer_from_account(): void
    {
        $account = $this->makeAccount(500);
        $this->app->make(ReconciliationService::class)->run($account->id, '450', User::factory()->create()->id);

        $this->assertSame('450.0000', (string) $account->fresh()->balance);
    }
}
