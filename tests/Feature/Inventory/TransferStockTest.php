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

class TransferStockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        foreach (['view-products', 'manage-products', 'record-stock-movements'] as $perm) {
            $admin->givePermissionTo(Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']));
        }
    }

    public function test_transfer_moves_stock_between_locations(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $product = Product::factory()->create();
        $from = StockLocation::factory()->create(['name' => 'Storage A']);
        $to = StockLocation::factory()->create(['name' => 'Storage B']);
        StockLevel::create(['product_id' => $product->id, 'location_id' => $from->id, 'quantity' => 100]);

        $response = $this->actingAs($admin)->post("/inventory/products/{$product->id}/transfer", [
            'from_location_id' => $from->id,
            'to_location_id' => $to->id,
            'quantity' => 30,
            'notes' => 'Restock',
        ]);

        $response->assertRedirect();
        $this->assertSame(70, (int) StockLevel::where('product_id', $product->id)->where('location_id', $from->id)->value('quantity'));
        $this->assertSame(30, (int) StockLevel::where('product_id', $product->id)->where('location_id', $to->id)->value('quantity'));

        $movements = StockMovement::orderBy('id')->get();
        $this->assertCount(2, $movements);
        $this->assertSame('transfer_out', $movements[0]->type->value);
        $this->assertSame(-30, (int) $movements[0]->quantity);
        $this->assertSame('transfer_in', $movements[1]->type->value);
        $this->assertSame(30, (int) $movements[1]->quantity);
    }

    public function test_transfer_rejects_insufficient_stock(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $product = Product::factory()->create();
        $from = StockLocation::factory()->create();
        $to = StockLocation::factory()->create();
        StockLevel::create(['product_id' => $product->id, 'location_id' => $from->id, 'quantity' => 5]);

        $response = $this->actingAs($admin)->post("/inventory/products/{$product->id}/transfer", [
            'from_location_id' => $from->id,
            'to_location_id' => $to->id,
            'quantity' => 10,
        ]);

        $response->assertSessionHasErrors('quantity');
        $this->assertSame(0, StockMovement::count());
    }

    public function test_transfer_rejects_same_location(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $product = Product::factory()->create();
        $loc = StockLocation::factory()->create();

        $response = $this->actingAs($admin)->post("/inventory/products/{$product->id}/transfer", [
            'from_location_id' => $loc->id,
            'to_location_id' => $loc->id,
            'quantity' => 5,
        ]);

        $response->assertSessionHasErrors('to_location_id');
    }
}
