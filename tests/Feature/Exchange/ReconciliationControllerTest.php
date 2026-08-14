<?php

declare(strict_types=1);

namespace Tests\Feature\Exchange;

use App\Models\CashDrawer;
use App\Models\CashMovement;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationControllerTest extends TestCase
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

    public function test_index_renders_for_admin(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($user)->get('/exchange/reconcile')->assertOk();
    }

    public function test_index_rejects_doctor(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('doctor');
        $this->actingAs($user)->get('/exchange/reconcile')->assertForbidden();
    }

    public function test_run_stamps_last_reconciled(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        CashDrawer::query()->firstOrCreate(['currency_code' => 'BDT'], ['balance' => '0.0000']);
        CashDrawer::query()->where('currency_code', 'BDT')->update(['balance' => '100.0000']);

        $response = $this->actingAs($user)->post('/exchange/reconcile/run', [
            'currency_code' => 'BDT',
            'counted_balance' => '100',
        ]);

        $response->assertRedirect(route('exchange.reconcile.index'));
        $this->assertNotNull(CashDrawer::query()->where('currency_code', 'BDT')->value('last_reconciled_at'));
    }

    public function test_run_with_variance_writes_adjustment(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        CashDrawer::query()->firstOrCreate(['currency_code' => 'BDT'], ['balance' => '0.0000']);
        CashDrawer::query()->where('currency_code', 'BDT')->update(['balance' => '100.0000']);

        $this->actingAs($user)->post('/exchange/reconcile/run', [
            'currency_code' => 'BDT',
            'counted_balance' => '90',
        ])->assertRedirect();

        $this->assertSame(1, CashMovement::query()->where('type', 'adjustment')->count());
    }
}
