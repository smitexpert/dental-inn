<?php

declare(strict_types=1);

namespace Tests\Feature\Doctors;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ToggleAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $perm = Permission::firstOrCreate(['name' => 'manage-doctors', 'guard_name' => 'web']);
        $admin->givePermissionTo($perm);
    }

    public function test_toggles_doctor_availability(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $doctor = Doctor::factory()->create(['is_available' => true]);

        $response = $this->actingAs($admin)->post("/doctors/{$doctor->id}/toggle-availability");
        $response->assertRedirect();
        $this->assertFalse($doctor->fresh()->is_available);

        $this->actingAs($admin)->post("/doctors/{$doctor->id}/toggle-availability");
        $this->assertTrue($doctor->fresh()->is_available);
    }
}
