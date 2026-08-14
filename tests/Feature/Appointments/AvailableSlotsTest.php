<?php

declare(strict_types=1);

namespace Tests\Feature\Appointments;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AvailableSlotsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-appointments', 'guard_name' => 'web']));
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'manage-appointments', 'guard_name' => 'web']));
    }

    public function test_returns_slots_for_scheduled_day(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $doctor = Doctor::factory()->create();

        $nextMonday = now()->next(Carbon::MONDAY)->format('Y-m-d');
        DoctorSchedule::factory()->create([
            'doctor_id' => $doctor->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get("/appointments/slots/{$doctor->id}?date={$nextMonday}&duration_minutes=30");
        $response->assertOk();
        $slots = $response->json('slots');
        $this->assertNotEmpty($slots);
    }

    public function test_returns_empty_when_day_off(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $doctor = Doctor::factory()->create();
        $date = now()->format('Y-m-d');

        $response = $this->actingAs($admin)->get("/appointments/slots/{$doctor->id}?date={$date}");
        $response->assertOk();
        $this->assertSame([], $response->json('slots'));
    }
}
