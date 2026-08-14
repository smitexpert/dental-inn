<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductSearchApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-products', 'guard_name' => 'web']));
    }

    public function test_returns_active_products_matching_term(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        Product::factory()->create(['name' => 'Composite resin A2', 'sku' => 'SKU-COMP', 'is_active' => true]);
        Product::factory()->create(['name' => 'Latex gloves', 'sku' => 'SKU-GLV', 'is_active' => true]);
        Product::factory()->inactive()->create(['name' => 'Composite flow']);

        $response = $this->actingAs($admin)->get('/inventory/products/api/search?q=composite');
        $response->assertOk();
        $data = $response->json('data');
        $names = collect($data)->pluck('name')->all();
        $this->assertContains('Composite resin A2', $names);
        $this->assertNotContains('Composite flow', $names);
    }
}
