<?php

declare(strict_types=1);

namespace Tests\Feature\Exchange;

use App\Models\CashDrawer;
use App\Models\User;
use App\Services\CashDrawerService;
use App\Services\ExchangeRateService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\ExchangeRateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExchangeReconcileCashVsLedgerCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            CurrencySeeder::class,
            ExchangeRateSeeder::class,
            ChartOfAccountsSeeder::class,
        ]);
    }

    public function test_command_reports_match_when_balances_zero(): void
    {
        $this->artisan('exchange:reconcile-cash-vs-ledger')
            ->expectsOutputToContain('Status: MATCH')
            ->assertSuccessful();
    }

    public function test_command_reports_drift_when_drawer_exceeds_ledger(): void
    {
        $user = User::factory()->create();
        CashDrawer::query()->firstOrCreate(['currency_code' => 'BDT'], ['balance' => '0.0000']);

        app(CashDrawerService::class)->cashIn('BDT', '500.00', 'Owner deposit', null, $user->id);

        $this->artisan('exchange:reconcile-cash-vs-ledger')
            ->expectsOutputToContain('Status: DRIFT')
            ->expectsOutputToContain('500.0000')
            ->assertSuccessful();
    }
}
