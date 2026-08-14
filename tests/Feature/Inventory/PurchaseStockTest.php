<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseStockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        foreach (['view-products', 'manage-products', 'record-stock-movements', 'view-stock-movements'] as $perm) {
            $admin->givePermissionTo(Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']));
        }
    }

    public function test_purchase_increases_stock_level(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $product = Product::factory()->create();
        $location = StockLocation::factory()->create();

        $response = $this->actingAs($admin)->post("/inventory/products/{$product->id}/purchase", [
            'location_id' => $location->id,
            'quantity' => 25,
            'unit_cost' => '12.5000',
            'reason' => 'Initial stock',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame(25, StockLevel::where('product_id', $product->id)->where('location_id', $location->id)->value('quantity'));
        $movement = StockMovement::where('product_id', $product->id)->latest('id')->first();
        $this->assertNotNull($movement);
        $this->assertSame('purchase', $movement->type->value);
        $this->assertSame(25, (int) $movement->quantity);
        $this->assertSame('12.5000', $movement->unit_cost);
        $this->assertSame($admin->id, $movement->performed_by);
    }

    public function test_quantity_must_be_positive(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $product = Product::factory()->create();
        $location = StockLocation::factory()->create();

        $response = $this->actingAs($admin)->post("/inventory/products/{$product->id}/purchase", [
            'location_id' => $location->id,
            'quantity' => 0,
        ]);

        $response->assertSessionHasErrors('quantity');
        $this->assertSame(0, StockMovement::count());
    }
}
