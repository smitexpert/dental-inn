<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShowProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-products', 'guard_name' => 'web']));
    }

    public function test_shows_product_with_lowest_quantity(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $product = Product::factory()->create(['reorder_level' => 10]);

        $response = $this->actingAs($admin)->get("/inventory/products/{$product->id}");

        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Inventory/Products/Show')
                ->where('product.id', $product->id)
                ->where('product.sku', $product->sku)
                ->etc(),
        );
    }
}
