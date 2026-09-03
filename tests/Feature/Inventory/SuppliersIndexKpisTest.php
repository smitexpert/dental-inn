<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuppliersIndexKpisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_index_returns_kpis(): void
    {
        Supplier::factory()->create(['name' => 'Acme Supply', 'is_active' => true]);
        Supplier::factory()->create(['name' => 'Beta Supply', 'is_active' => false]);

        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->get('/inventory/suppliers');
        $response->assertOk();

        $kpis = $response->viewData('page')['props']['kpis'] ?? [];
        $this->assertCount(3, $kpis);
        $this->assertSame('total', $kpis[0]['id']);
        $this->assertSame((string) Supplier::count(), $kpis[0]['value']);
        $this->assertSame('active', $kpis[1]['id']);
        $this->assertSame('1', $kpis[1]['value']);
        $this->assertSame('top', $kpis[2]['id']);
    }
}
