<?php

declare(strict_types=1);

namespace Tests\Feature\Exchange;

use App\Models\CashDrawer;
use App\Models\CashMovement;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\ExchangeRateSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashMovementControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleSeeder::class,
            CurrencySeeder::class,
            ExchangeRateSeeder::class,
        ]);
        // ensure drawers exist
        CashDrawer::query()->firstOrCreate(['currency_code' => 'BDT'], ['balance' => '0.0000']);
        CashDrawer::query()->firstOrCreate(['currency_code' => 'USD'], ['balance' => '0.0000']);
    }

    public function test_index_renders(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($user)->get('/exchange/movements')->assertOk();
    }

    public function test_create_renders_for_receptionist(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('receptionist');
        $this->actingAs($user)->get('/exchange/movements/create')->assertOk();
    }

    public function test_create_rejects_unauthorized(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('doctor');
        $this->actingAs($user)->get('/exchange/movements/create')->assertStatus(403);
    }

    public function test_store_cash_in_creates_movement(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $response = $this->actingAs($user)->post('/exchange/movements', [
            'type' => 'cash_in',
            'currency_code' => 'BDT',
            'amount' => '100',
            'reason' => 'Test deposit',
        ]);

        $response->assertRedirect(route('exchange.movements.index'));
        $this->assertSame(1, CashMovement::query()->where('type', 'cash_in')->count());
        $this->assertSame('100.0000', (string) CashDrawer::query()->where('currency_code', 'BDT')->value('balance'));
    }

    public function test_store_cash_in_requires_reason(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($user)->post('/exchange/movements', [
            'type' => 'cash_in',
            'currency_code' => 'BDT',
            'amount' => '100',
        ])->assertSessionHasErrors('reason');
    }

    public function test_store_exchange_creates_two_linked_movements(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($user)->post('/exchange/movements', [
            'type' => 'cash_in',
            'currency_code' => 'USD',
            'amount' => '500',
            'reason' => 'Initial USD',
        ])->assertRedirect();

        $this->actingAs($user)->post('/exchange/movements', [
            'type' => 'exchange_out',
            'currency_code' => 'USD',
            'to_currency_code' => 'BDT',
            'amount' => '10',
        ])->assertRedirect();

        $this->assertSame(1, CashMovement::query()->where('type', 'exchange_in')->count());
        $this->assertSame(1, CashMovement::query()->where('type', 'exchange_out')->count());
    }
}
