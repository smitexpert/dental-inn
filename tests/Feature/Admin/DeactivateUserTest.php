<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeactivateUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $perm = Permission::firstOrCreate(['name' => 'manage-users', 'guard_name' => 'web']);
        $admin->givePermissionTo($perm);
    }

    public function test_toggles_user_active_status(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $target = User::factory()->create(['is_active' => true]);
        $this->actingAs($admin);

        $this->post("/admin/users/{$target->id}/toggle-active")->assertRedirect();
        $this->assertFalse($target->fresh()->is_active);

        $this->post("/admin/users/{$target->id}/toggle-active")->assertRedirect();
        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_prevents_admin_from_deactivating_themselves(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        $this->post("/admin/users/{$admin->id}/toggle-active")->assertStatus(403);
    }
}
