<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ListRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $doctor = Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        $perm = Permission::firstOrCreate(['name' => 'manage-roles', 'guard_name' => 'web']);
        $admin->givePermissionTo($perm);
    }

    public function test_lists_roles_for_admin(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        $response = $this->get('/admin/roles');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/Roles/Index'));
    }

    public function test_forbids_non_admin_from_listing(): void
    {
        $doctor = User::factory()->create(['is_active' => true])->assignRole('doctor');
        $this->actingAs($doctor);

        $this->get('/admin/roles')->assertStatus(403);
    }
}