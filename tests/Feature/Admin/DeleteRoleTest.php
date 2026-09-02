<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeleteRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $perm = Permission::firstOrCreate(['name' => 'manage-roles', 'guard_name' => 'web']);
        $admin->givePermissionTo($perm);
    }

    public function test_deletes_unused_role(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        $role = Role::create(['name' => 'unused', 'guard_name' => 'web']);

        $response = $this->delete("/admin/roles/{$role->id}");
        $response->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_refuses_to_delete_role_with_users(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        $role = Role::create(['name' => 'in-use', 'guard_name' => 'web']);
        User::factory()->create(['is_active' => true])->assignRole('in-use');

        $response = $this->delete("/admin/roles/{$role->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }
}
