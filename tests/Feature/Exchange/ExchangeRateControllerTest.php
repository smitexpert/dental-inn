<?php

declare(strict_types=1);

namespace Tests\Feature\Exchange;

use App\Models\ExchangeRate;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExchangeRateControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleSeeder::class,
            CurrencySeeder::class,
        ]);
    }

    public function test_index_renders_for_viewer(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');

        $this->actingAs($user)->get('/exchange/rates')->assertOk();
    }

    public function test_store_creates_rate_for_admin(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($user)->post('/exchange/rates', [
            'from_currency_code' => 'USD',
            'to_currency_code' => 'BDT',
            'rate' => '110.0',
            'effective_at' => '2026-08-14',
        ]);

        $response->assertRedirect(route('exchange.rates.index'));
        $this->assertSame(1, ExchangeRate::query()->where('from_currency_code', 'USD')->count());
    }

    public function test_store_rejects_same_currency(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');

        $this->actingAs($user)->post('/exchange/rates', [
            'from_currency_code' => 'USD',
            'to_currency_code' => 'USD',
            'rate' => '1.0',
            'effective_at' => '2026-08-14',
        ])->assertSessionHasErrors();
    }

    public function test_store_rejects_non_admin(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('doctor');

        $this->actingAs($user)->post('/exchange/rates', [
            'from_currency_code' => 'USD',
            'to_currency_code' => 'BDT',
            'rate' => '110.0',
            'effective_at' => '2026-08-14',
        ])->assertForbidden();
    }

    public function test_destroy_removes_rate(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $rate = ExchangeRate::create([
            'from_currency_code' => 'USD',
            'to_currency_code' => 'BDT',
            'rate' => '110.0',
            'effective_at' => '2026-08-14',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->delete("/exchange/rates/{$rate->id}")->assertRedirect();
        $this->assertNull(ExchangeRate::query()->find($rate->id));
    }
}
