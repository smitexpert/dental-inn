<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedAuthPropsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_user_receives_full_role_and_permission_arrays(): void
    {
        $this->seed(UserSeeder::class);

        $admin = User::where('email', 'admin@clinic.test')->first();
        $this->assertNotNull($admin, 'admin seed user must exist');

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(function ($page): void {
                $roles = $page->toArray()['props']['auth']['user']['roles'] ?? null;
                $permissions = $page->toArray()['props']['auth']['user']['permissions'] ?? null;

                $this->assertIsArray($roles);
                $this->assertIsArray($permissions);
                $this->assertContains('admin', $roles);
                $this->assertContains('view-dashboard', $permissions);
                $this->assertContains('manage-users', $permissions);
            });
    }

    public function test_doctor_user_receives_only_doctor_role_and_expected_permissions(): void
    {
        $this->seed(UserSeeder::class);

        $doctor = User::where('email', 'doctor@clinic.test')->first();
        $this->assertNotNull($doctor, 'doctor seed user must exist');

        $this->actingAs($doctor)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(function ($page): void {
                $props = $page->toArray()['props'];
                $roles = $props['auth']['user']['roles'] ?? null;
                $permissions = $props['auth']['user']['permissions'] ?? null;

                // Roles must be a flat array of strings — not a Laravel
                // Collection — so the client-side .includes() works.
                $this->assertIsArray($roles);
                $this->assertContains('doctor', $roles);
                $this->assertNotContains('admin', $roles);

                $this->assertIsArray($permissions);
                $this->assertContains('view-dashboard', $permissions);
                $this->assertContains('view-appointments', $permissions);
                $this->assertContains('view-patients', $permissions);

                // doctors do not get admin-only or receptionist-only permissions.
                $this->assertNotContains('manage-users', $permissions);
                $this->assertNotContains('send-notifications', $permissions);
            });
    }

    public function test_receptionist_user_receives_only_receptionist_role_and_expected_permissions(): void
    {
        $this->seed(UserSeeder::class);

        $receptionist = User::where('email', 'receptionist@clinic.test')->first();
        $this->assertNotNull($receptionist, 'receptionist seed user must exist');

        $this->actingAs($receptionist)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(function ($page): void {
                $props = $page->toArray()['props'];
                $roles = $props['auth']['user']['roles'] ?? null;
                $permissions = $props['auth']['user']['permissions'] ?? null;

                $this->assertIsArray($roles);
                $this->assertContains('receptionist', $roles);
                $this->assertNotContains('admin', $roles);

                $this->assertIsArray($permissions);
                $this->assertContains('manage-appointments', $permissions);
                $this->assertContains('manage-patients', $permissions);
                $this->assertContains('manage-invoices', $permissions);
                $this->assertContains('send-notifications', $permissions);

                // receptionist does not get admin-only permissions.
                $this->assertNotContains('manage-users', $permissions);
                $this->assertNotContains('manage-settings', $permissions);
            });
    }

    public function test_guest_receives_null_user_in_shared_auth_props(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(function ($page): void {
                $props = $page->toArray()['props'];
                $this->assertNull($props['auth']['user']);
            });
    }
}
