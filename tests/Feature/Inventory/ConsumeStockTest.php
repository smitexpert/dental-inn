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

class ConsumeStockTest extends TestCase
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

    protected function seedStock(Product $product, StockLocation $location, int $qty): void
    {
        StockLevel::create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => $qty,
        ]);
    }

    public function test_consume_decreases_stock_level(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $product = Product::factory()->create();
        $location = StockLocation::factory()->create();
        $this->seedStock($product, $location, 50);

        $response = $this->actingAs($admin)->post("/inventory/products/{$product->id}/consume", [
            'location_id' => $location->id,
            'quantity' => 10,
            'reason' => 'Used for patient',
        ]);

        $response->assertRedirect();
        $this->assertSame(40, (int) StockLevel::where('product_id', $product->id)->where('location_id', $location->id)->value('quantity'));

        $movement = StockMovement::latest('id')->first();
        $this->assertNotNull($movement);
        $this->assertSame('consumption', $movement->type->value);
        $this->assertSame(-10, (int) $movement->quantity);
    }

    public function test_consume_rejects_overdraw(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $product = Product::factory()->create();
        $location = StockLocation::factory()->create();
        $this->seedStock($product, $location, 5);

        $response = $this->actingAs($admin)->post("/inventory/products/{$product->id}/consume", [
            'location_id' => $location->id,
            'quantity' => 10,
        ]);

        $response->assertSessionHasErrors('quantity');
        $this->assertSame(5, (int) StockLevel::where('product_id', $product->id)->where('location_id', $location->id)->value('quantity'));
        $this->assertSame(0, StockMovement::count());
    }

    public function test_consume_rejects_no_existing_stock(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $product = Product::factory()->create();
        $location = StockLocation::factory()->create();

        $response = $this->actingAs($admin)->post("/inventory/products/{$product->id}/consume", [
            'location_id' => $location->id,
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors('quantity');
    }
}
