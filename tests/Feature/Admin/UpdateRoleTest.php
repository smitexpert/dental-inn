<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdateRoleTest extends TestCase
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
        Permission::firstOrCreate(['name' => 'manage-patients', 'guard_name' => 'web']);
    }

    public function test_updates_role_name_and_permissions(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        $role = Role::create(['name' => 'nurse', 'guard_name' => 'web']);
        $role->syncPermissions(['view-patients']);

        $response = $this->patch("/admin/roles/{$role->id}", [
            'name' => 'senior-nurse',
            'permissions' => ['view-patients', 'view-appointments'],
        ]);

        $response->assertRedirect(route('admin.roles.index', [], false));

        $role->refresh();
        $this->assertSame('senior-nurse', $role->name);
        $this->assertSame(
            ['view-appointments', 'view-patients'],
            $role->permissions->pluck('name')->sort()->values()->all()
        );
    }

    public function test_sync_replaces_not_appends(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        $role = Role::create(['name' => 'nurse', 'guard_name' => 'web']);
        $role->syncPermissions(['view-patients', 'view-appointments', 'manage-patients']);

        $this->patch("/admin/roles/{$role->id}", [
            'name' => 'nurse',
            'permissions' => ['view-patients'],
        ])->assertRedirect();

        $role->refresh();
        $this->assertSame(['view-patients'], $role->permissions->pluck('name')->all());
    }
}
