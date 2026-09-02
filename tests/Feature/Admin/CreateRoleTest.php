<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $perm = Permission::firstOrCreate(['name' => 'manage-roles', 'guard_name' => 'web']);
        $admin->givePermissionTo($perm);

        Permission::firstOrCreate(['name' => 'view-patients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view-appointments', 'guard_name' => 'web']);
    }

    public function test_creates_role_with_synced_permissions(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        $response = $this->post('/admin/roles', [
            'name' => 'nurse',
            'permissions' => ['view-patients', 'view-appointments'],
        ]);

        $response->assertRedirect(route('admin.roles.index'));

        $role = Role::where('name', 'nurse')->firstOrFail();
        $this->assertSame(['view-appointments', 'view-patients'], $role->permissions->pluck('name')->sort()->values()->all());
    }

    public function test_creates_role_with_no_permissions(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        $this->post('/admin/roles', [
            'name' => 'observer',
            'permissions' => [],
        ])->assertRedirect(route('admin.roles.index'));

        $role = Role::where('name', 'observer')->firstOrFail();
        $this->assertSame(0, $role->permissions->count());
    }

    public function test_rejects_duplicate_name(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        Role::create(['name' => 'nurse', 'guard_name' => 'web']);

        $this->post('/admin/roles', [
            'name' => 'nurse',
            'permissions' => [],
        ])->assertSessionHasErrors('name');
    }

    public function test_rejects_unknown_permission(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        $this->post('/admin/roles', [
            'name' => 'nurse',
            'permissions' => ['does-not-exist'],
        ])->assertSessionHasErrors('permissions.0');
    }
}