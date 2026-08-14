<?php

declare(strict_types=1);

namespace Tests\Feature\Exchange;

use App\Enums\CashMovementType;
use App\Models\CashDrawer;
use App\Models\User;
use App\Services\CashDrawerService;
use App\Services\ExchangeRateService;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class CashDrawerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CurrencySeeder::class);
        // ensure a drawer exists for BDT
        CashDrawer::query()->firstOrCreate(['currency_code' => 'BDT'], ['balance' => '0.0000']);
    }

    public function test_cash_in_increments_drawer_and_writes_movement(): void
    {
        $service = app(CashDrawerService::class);
        $user = User::factory()->create();

        $movement = $service->cashIn('BDT', '500.00', 'Owner deposit', null, $user->id);

        $this->assertSame(CashMovementType::CashIn, $movement->type);
        $this->assertSame('500.0000', (string) $movement->amount);
        $this->assertSame('500.0000', (string) CashDrawer::query()->where('currency_code', 'BDT')->value('balance'));
    }

    public function test_cash_out_decrements_drawer(): void
    {
        $service = app(CashDrawerService::class);
        $user = User::factory()->create();

        $service->cashIn('BDT', '1000.00', 'Initial', null, $user->id);
        $movement = $service->cashOut('BDT', '250.00', 'Petty cash', null, $user->id);

        $this->assertSame(CashMovementType::CashOut, $movement->type);
        $this->assertSame('-250.0000', (string) $movement->amount);
        $this->assertSame('750.0000', (string) CashDrawer::query()->where('currency_code', 'BDT')->value('balance'));
    }

    public function test_cash_out_rejects_insufficient_balance(): void
    {
        $service = app(CashDrawerService::class);
        $user = User::factory()->create();

        $this->expectException(RuntimeException::class);
        $service->cashOut('BDT', '100.00', 'Too much', null, $user->id);
    }

    public function test_exchange_writes_two_linked_movements(): void
    {
        $user = User::factory()->create();
        app(ExchangeRateService::class)->setRate('USD', 'BDT', '110', Carbon::now(), null);

        CashDrawer::query()->firstOrCreate(['currency_code' => 'USD'], ['balance' => '0.0000']);
        $service = app(CashDrawerService::class);

        $service->cashIn('USD', '100.00', 'Initial USD', null, $user->id);

        [$out, $in] = $service->exchange('USD', 'BDT', '10.00', $user->id);

        $this->assertSame(CashMovementType::ExchangeOut, $out->type);
        $this->assertSame(CashMovementType::ExchangeIn, $in->type);
        $this->assertSame('-10.0000', (string) $out->amount);
        $this->assertSame('1100.0000', (string) $in->amount);
        $this->assertNotNull($out->exchange_group_id);
        $this->assertSame($out->exchange_group_id, $in->exchange_group_id);
        $this->assertSame('90.0000', (string) CashDrawer::query()->where('currency_code', 'USD')->value('balance'));
        $this->assertSame('1100.0000', (string) CashDrawer::query()->where('currency_code', 'BDT')->value('balance'));
    }

    public function test_exchange_rejects_same_currency(): void
    {
        $service = app(CashDrawerService::class);
        $user = User::factory()->create();

        $this->expectException(RuntimeException::class);
        $service->exchange('BDT', 'BDT', '100.00', $user->id);
    }

    public function test_exchange_throws_when_no_rate(): void
    {
        $service = app(CashDrawerService::class);
        $user = User::factory()->create();

        $this->expectException(RuntimeException::class);
        $service->exchange('EUR', 'GBP', '50.00', $user->id);
    }

    public function test_exchange_rejects_when_source_insufficient(): void
    {
        $user = User::factory()->create();
        app(ExchangeRateService::class)->setRate('USD', 'BDT', '110', Carbon::now(), null);
        CashDrawer::query()->firstOrCreate(['currency_code' => 'USD'], ['balance' => '0.0000']);
        $service = app(CashDrawerService::class);

        $this->expectException(RuntimeException::class);
        $service->exchange('USD', 'BDT', '100.00', $user->id);
    }

    public function test_adjust_can_drive_balance_negative_check(): void
    {
        $service = app(CashDrawerService::class);
        $user = User::factory()->create();

        $this->expectException(RuntimeException::class);
        $service->adjust('BDT', '-100.00', 'Over-adjust', $user->id);
    }

    public function test_adjust_writes_movement(): void
    {
        $service = app(CashDrawerService::class);
        $user = User::factory()->create();

        $service->cashIn('BDT', '100.00', 'Seed', null, $user->id);
        $movement = $service->adjust('BDT', '50.00', 'Found extra', $user->id);

        $this->assertSame(CashMovementType::Adjustment, $movement->type);
        $this->assertSame('50.0000', (string) $movement->amount);
        $this->assertSame('150.0000', (string) CashDrawer::query()->where('currency_code', 'BDT')->value('balance'));
    }

    public function test_movement_immutability(): void
    {
        $service = app(CashDrawerService::class);
        $user = User::factory()->create();
        $movement = $service->cashIn('BDT', '1.00', 'Test', null, $user->id);

        $this->expectException(RuntimeException::class);
        $movement->amount = '999.0000';
        $movement->save();
    }
}
