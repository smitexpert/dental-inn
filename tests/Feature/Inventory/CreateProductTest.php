<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'manage-products', 'guard_name' => 'web']));
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-products', 'guard_name' => 'web']));
    }

    public function test_creates_product_with_valid_data(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->post('/inventory/products', [
            'sku' => 'SKU-AB1234',
            'name' => 'Composite resin A2',
            'category' => 'consumable',
            'unit' => 'syringe',
            'cost_price' => 50,
            'selling_price' => 75,
            'reorder_level' => 5,
            'reorder_quantity' => 20,
        ]);

        $response->assertRedirect();
        $this->assertSame(1, Product::count());
        $product = Product::first();
        $this->assertSame('SKU-AB1234', $product->sku);
        $this->assertSame('Composite resin A2', $product->name);
        $this->assertSame(5, $product->reorder_level);
        $this->assertSame(20, $product->reorder_quantity);
    }

    public function test_sku_must_be_unique(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        Product::factory()->create(['sku' => 'SKU-DUP']);

        $response = $this->actingAs($admin)->post('/inventory/products', [
            'sku' => 'SKU-DUP',
            'name' => 'Another product',
            'category' => 'consumable',
            'unit' => 'piece',
        ]);

        $response->assertSessionHasErrors('sku');
        $this->assertSame(1, Product::count());
    }

    public function test_requires_name_category_unit(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->post('/inventory/products', [
            'sku' => 'SKU-MIN',
        ]);

        $response->assertSessionHasErrors(['name', 'category', 'unit']);
    }

    public function test_invalid_category_rejected(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->post('/inventory/products', [
            'sku' => 'SKU-BAD',
            'name' => 'X',
            'category' => 'not-a-category',
            'unit' => 'piece',
        ]);

        $response->assertSessionHasErrors('category');
    }

    public function test_receptionist_cannot_create(): void
    {
        $receptionist = User::factory()->create(['is_active' => true]);
        $role = Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'view-products', 'guard_name' => 'web']));
        $receptionist->assignRole('receptionist');

        $response = $this->actingAs($receptionist)->post('/inventory/products', [
            'sku' => 'SKU-DENY',
            'name' => 'X',
            'category' => 'consumable',
            'unit' => 'piece',
        ]);

        $response->assertStatus(403);
        $this->assertSame(0, Product::count());
    }
}
