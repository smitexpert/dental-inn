<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LowStockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-products', 'guard_name' => 'web']));
    }

    public function test_dashboard_shows_low_stock_count(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $location = StockLocation::factory()->create();

        $low = Product::factory()->create(['reorder_level' => 10, 'name' => 'Low product']);
        StockLevel::create(['product_id' => $low->id, 'location_id' => $location->id, 'quantity' => 5]);

        $ok = Product::factory()->create(['reorder_level' => 10, 'name' => 'OK product']);
        StockLevel::create(['product_id' => $ok->id, 'location_id' => $location->id, 'quantity' => 50]);

        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('stats.low_stock_items.value', 1)
                ->etc(),
        );
    }

    public function test_untracked_products_excluded_from_low_count(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $location = StockLocation::factory()->create();

        Product::factory()->notTracked()->create(['reorder_level' => 10, 'name' => 'Untracked']);
        StockLevel::create(['product_id' => Product::factory()->notTracked()->create()->id, 'location_id' => $location->id, 'quantity' => 1]);

        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('stats.low_stock_items.value', 0)
                ->etc(),
        );
    }

    public function test_low_stock_page_lists_only_low_products(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $location = StockLocation::factory()->create();

        $low = Product::factory()->create(['reorder_level' => 5]);
        StockLevel::create(['product_id' => $low->id, 'location_id' => $location->id, 'quantity' => 2]);

        $high = Product::factory()->create(['reorder_level' => 5]);
        StockLevel::create(['product_id' => $high->id, 'location_id' => $location->id, 'quantity' => 50]);

        $response = $this->actingAs($admin)->get('/inventory/low-stock');
        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Inventory/LowStock')
                ->where('products.data.0.id', $low->id)
                ->missing('products.data.1')
                ->etc(),
        );
    }
}
