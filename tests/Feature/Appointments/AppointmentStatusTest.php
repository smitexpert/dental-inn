<?php

declare(strict_types=1);

namespace Tests\Feature\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppointmentStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        foreach (['view-appointments', 'manage-appointments', 'cancel-appointments', 'check-in-appointments'] as $p) {
            $admin->givePermissionTo(Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']));
        }
    }

    public function test_scheduled_to_confirmed(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $appt = Appointment::factory()->create(['status' => AppointmentStatus::Scheduled]);

        $this->actingAs($admin)->patch("/appointments/{$appt->id}/status", [
            'status' => 'confirmed',
        ])->assertRedirect();

        $this->assertSame(AppointmentStatus::Confirmed, $appt->fresh()->status);
    }

    public function test_rejects_invalid_transition(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $appt = Appointment::factory()->create(['status' => AppointmentStatus::Completed]);

        $this->actingAs($admin)->patch("/appointments/{$appt->id}/status", [
            'status' => 'confirmed',
        ])->assertStatus(422);
    }

    public function test_cancellation_records_reason(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $appt = Appointment::factory()->create(['status' => AppointmentStatus::Scheduled]);

        $this->actingAs($admin)->patch("/appointments/{$appt->id}/status", [
            'status' => 'cancelled',
            'reason' => 'Patient sick',
        ])->assertRedirect();

        $fresh = $appt->fresh();
        $this->assertSame(AppointmentStatus::Cancelled, $fresh->status);
        $this->assertNotNull($fresh->cancelled_at);
        $this->assertSame('Patient sick', $fresh->cancelled_reason);
    }
}
