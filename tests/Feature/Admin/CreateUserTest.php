<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        $perm = Permission::firstOrCreate(['name' => 'manage-users', 'guard_name' => 'web']);
        $admin->givePermissionTo($perm);
    }

    public function test_creates_new_user_with_role(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        $response = $this->post('/admin/users', [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'doctor',
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/users');
        $jane = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($jane);
        $this->assertTrue($jane->hasRole('doctor'));
    }
}
