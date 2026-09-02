<?php

declare(strict_types=1);

namespace Tests\Feature\Doctors;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdateScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $perm = Permission::firstOrCreate(['name' => 'manage-doctors', 'guard_name' => 'web']);
        $admin->givePermissionTo($perm);
    }

    public function test_replaces_schedule(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $doctor = Doctor::factory()->create();

        $response = $this->actingAs($admin)->patch("/doctors/{$doctor->id}/schedule", [
            'schedules' => [
                ['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '16:00', 'is_active' => true],
                ['day_of_week' => 2, 'start_time' => '08:00', 'end_time' => '16:00', 'is_active' => true],
            ],
        ]);

        $response->assertRedirect();
        $this->assertSame(2, $doctor->schedules()->count());
    }
}
