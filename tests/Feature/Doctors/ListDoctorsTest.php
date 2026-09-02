<?php

declare(strict_types=1);

namespace Tests\Feature\Doctors;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ListDoctorsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $perm = Permission::firstOrCreate(['name' => 'view-doctors', 'guard_name' => 'web']);
        $admin->givePermissionTo($perm);
    }

    public function test_admin_can_view_doctors(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        Doctor::factory()->count(2)->create();

        $this->actingAs($admin)->get('/doctors')->assertOk();
    }

    public function test_unauthenticated_cannot_view_doctors(): void
    {
        $this->get('/doctors')->assertRedirect('/login');
    }
}
