<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateStockLocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'manage-products', 'guard_name' => 'web']));
    }

    public function test_creates_location(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->post('/inventory/locations', [
            'name' => 'Operatory 1',
            'location_type' => 'operatory',
        ]);

        $response->assertRedirect();
        $this->assertSame(1, StockLocation::count());
        $this->assertSame('operatory', StockLocation::first()->location_type->value);
    }

    public function test_requires_name_and_type(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->post('/inventory/locations', []);

        $response->assertSessionHasErrors(['name', 'location_type']);
    }
}
