<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeleteUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $perm = Permission::firstOrCreate(['name' => 'manage-users', 'guard_name' => 'web']);
        $admin->givePermissionTo($perm);
    }

    public function test_deletes_a_user(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $target = User::factory()->create(['is_active' => true]);
        $this->actingAs($admin);

        $this->delete("/admin/users/{$target->id}")->assertRedirect('/admin/users');
        $this->assertNull(User::find($target->id));
    }

    public function test_prevents_self_deletion(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);
        $this->delete("/admin/users/{$admin->id}")->assertStatus(403);
    }
}
