<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductsIndexKpisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-products', 'guard_name' => 'web']));
    }

    public function test_index_returns_kpis(): void
    {
        Product::factory()->count(3)->create();

        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->get('/inventory/products');
        $response->assertOk();

        $kpis = $response->viewData('page')['props']['kpis'] ?? [];
        $this->assertCount(4, $kpis);
        $this->assertSame('total', $kpis[0]['id']);
        $this->assertSame('number', $kpis[0]['format']);
        $this->assertSame((string) Product::count(), $kpis[0]['value']);
        $this->assertSame('low', $kpis[1]['id']);
        $this->assertSame('out', $kpis[2]['id']);
        $this->assertSame('value', $kpis[3]['id']);
        $this->assertSame('currency', $kpis[3]['format']);
    }
}
