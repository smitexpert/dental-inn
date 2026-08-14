<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateSupplierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        foreach (['manage-suppliers', 'view-suppliers'] as $perm) {
            $admin->givePermissionTo(Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']));
        }
    }

    public function test_creates_supplier(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->post('/inventory/suppliers', [
            'name' => 'Acme Dental Supply',
            'phone' => '+1-555-0100',
            'email' => 'orders@acme.test',
        ]);

        $response->assertRedirect();
        $this->assertSame(1, Supplier::count());
        $this->assertSame('Acme Dental Supply', Supplier::first()->name);
    }

    public function test_unique_name(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        Supplier::factory()->create(['name' => 'Acme']);

        $response = $this->actingAs($admin)->post('/inventory/suppliers', [
            'name' => 'Acme',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, Supplier::count());
    }
}
