<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdateUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        $perm = Permission::firstOrCreate(['name' => 'manage-users', 'guard_name' => 'web']);
        $admin->givePermissionTo($perm);
    }

    public function test_updates_user(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $target = User::factory()->create(['is_active' => true])->assignRole('receptionist');
        $this->actingAs($admin);

        $response = $this->patch("/admin/users/{$target->id}", [
            'name' => 'Renamed',
            'email' => $target->email,
            'role' => 'doctor',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $fresh = $target->fresh();
        $this->assertSame('Renamed', $fresh->name);
        $this->assertTrue($fresh->hasRole('doctor'));
    }
}
