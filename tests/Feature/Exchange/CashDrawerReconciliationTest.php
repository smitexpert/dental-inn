<?php

declare(strict_types=1);

namespace Tests\Feature\Exchange;

use App\Enums\CashMovementType;
use App\Models\CashDrawer;
use App\Models\User;
use App\Services\CashDrawerReconciliationService;
use App\Services\CashDrawerService;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashDrawerReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CurrencySeeder::class);
    }

    public function test_reconcile_with_matching_balance_writes_zero_adjustment(): void
    {
        $user = User::factory()->create();
        CashDrawer::query()->firstOrCreate(['currency_code' => 'BDT'], ['balance' => '0.0000']);
        app(CashDrawerService::class)->cashIn('BDT', '100.00', 'Seed', null, $user->id);

        $movement = app(CashDrawerReconciliationService::class)->run('BDT', '100.00', $user->id);

        $this->assertSame(CashMovementType::Adjustment, $movement->type);
        $this->assertSame('0.0000', (string) $movement->amount);
        $this->assertSame('100.0000', (string) CashDrawer::query()->where('currency_code', 'BDT')->value('balance'));
        $this->assertSame('100.0000', (string) CashDrawer::query()->where('currency_code', 'BDT')->value('last_reconciled_balance'));
    }

    public function test_reconcile_with_variance_creates_adjustment_movement(): void
    {
        $user = User::factory()->create();
        CashDrawer::query()->firstOrCreate(['currency_code' => 'BDT'], ['balance' => '0.0000']);
        app(CashDrawerService::class)->cashIn('BDT', '200.00', 'Seed', null, $user->id);

        $movement = app(CashDrawerReconciliationService::class)->run('BDT', '180.00', $user->id);

        $this->assertSame(CashMovementType::Adjustment, $movement->type);
        $this->assertSame('-20.0000', (string) $movement->amount);
        $this->assertSame('180.0000', (string) CashDrawer::query()->where('currency_code', 'BDT')->value('balance'));
        $this->assertNotNull(CashDrawer::query()->where('currency_code', 'BDT')->value('last_reconciled_at'));
    }

    public function test_reconcile_rejects_negative_counted(): void
    {
        $service = app(CashDrawerReconciliationService::class);
        $user = User::factory()->create();

        $this->expectException(\RuntimeException::class);
        $service->run('BDT', '-1', $user->id);
    }
}
