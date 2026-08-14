<?php

declare(strict_types=1);

namespace Tests\Feature\Exchange;

use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class ExchangeRateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CurrencySeeder::class);
    }

    public function test_get_rate_same_currency_returns_synthetic_one(): void
    {
        $rate = app(ExchangeRateService::class)->getRate('BDT', 'BDT');

        $this->assertSame('1.00000000', (string) $rate->rate);
    }

    public function test_get_rate_returns_direct_pair(): void
    {
        Carbon::setTestNow('2026-08-14 12:00:00');
        app(ExchangeRateService::class)->setRate('USD', 'BDT', '110', now(), null);
        Carbon::setTestNow(null);

        $rate = app(ExchangeRateService::class)->getRate('USD', 'BDT');

        $this->assertSame('110.00000000', (string) $rate->rate);
    }

    public function test_get_rate_falls_back_to_inverse(): void
    {
        Carbon::setTestNow('2026-08-14 12:00:00');
        app(ExchangeRateService::class)->setRate('BDT', 'USD', '0.00909091', now(), null);
        Carbon::setTestNow(null);

        $rate = app(ExchangeRateService::class)->getRate('USD', 'BDT');

        // 1 / 0.00909091 ≈ 110
        $this->assertEqualsWithDelta(110.0, (float) $rate->rate, 0.5);
    }

    public function test_get_rate_throws_when_missing(): void
    {
        $this->expectException(RuntimeException::class);
        app(ExchangeRateService::class)->getRate('EUR', 'GBP');
    }

    public function test_set_rate_is_idempotent_on_triple(): void
    {
        $at = Carbon::parse('2026-08-14 12:00:00');
        app(ExchangeRateService::class)->setRate('USD', 'BDT', '110', $at, null);
        app(ExchangeRateService::class)->setRate('USD', 'BDT', '111', $at, null);

        $this->assertSame(1, ExchangeRate::query()->where('from_currency_code', 'USD')->count());
        $this->assertSame('111.00000000', (string) ExchangeRate::query()->first()->rate);
    }

    public function test_set_rate_rejects_self_pair(): void
    {
        $this->expectException(RuntimeException::class);
        app(ExchangeRateService::class)->setRate('USD', 'USD', '1', now(), null);
    }

    public function test_set_rate_rejects_zero(): void
    {
        $this->expectException(RuntimeException::class);
        app(ExchangeRateService::class)->setRate('USD', 'BDT', '0', now(), null);
    }
}
