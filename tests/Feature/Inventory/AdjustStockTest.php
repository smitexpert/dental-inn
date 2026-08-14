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

class AdjustStockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        foreach (['view-products', 'manage-products'] as $perm) {
            $admin->givePermissionTo(Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']));
        }
    }

    public function test_adjust_sets_absolute_quantity(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $product = Product::factory()->create();
        $location = StockLocation::factory()->create();
        StockLevel::create(['product_id' => $product->id, 'location_id' => $location->id, 'quantity' => 50]);

        $response = $this->actingAs($admin)->post("/inventory/products/{$product->id}/adjust", [
            'location_id' => $location->id,
            'quantity' => 30,
            'reason' => 'Stocktake correction',
        ]);

        $response->assertRedirect();
        $this->assertSame(30, (int) StockLevel::where('product_id', $product->id)->where('location_id', $location->id)->value('quantity'));

        $movement = StockMovement::latest('id')->first();
        $this->assertNotNull($movement);
        $this->assertSame('adjustment', $movement->type->value);
        $this->assertSame(-20, (int) $movement->quantity);
        $this->assertSame('Stocktake correction', $movement->reason);
    }

    public function test_adjust_requires_reason(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $product = Product::factory()->create();
        $location = StockLocation::factory()->create();

        $response = $this->actingAs($admin)->post("/inventory/products/{$product->id}/adjust", [
            'location_id' => $location->id,
            'quantity' => 10,
        ]);

        $response->assertSessionHasErrors('reason');
    }
}
