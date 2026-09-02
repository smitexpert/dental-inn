<?php

declare(strict_types=1);

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateAppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-appointments', 'guard_name' => 'web']));
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'manage-appointments', 'guard_name' => 'web']));
    }

    public function test_creates_appointment(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $doctor = Doctor::factory()->create();
        $patient = Patient::factory()->create();

        $response = $this->actingAs($admin)->post('/appointments', [
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'scheduled_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'duration_minutes' => 30,
        ]);

        $response->assertRedirect('/appointments');
        $this->assertSame(1, Appointment::count());
        $this->assertSame($admin->id, Appointment::first()->created_by);
        $this->assertSame($patient->id, Appointment::first()->patient_id);
    }

    public function test_detects_overlap(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $doctor = Doctor::factory()->create();
        $patientA = Patient::factory()->create();
        $patientB = Patient::factory()->create();
        $when = now()->addDay()->setTime(10, 0);
        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patientA->id,
            'scheduled_at' => $when,
            'duration_minutes' => 30,
        ]);

        $response = $this->actingAs($admin)->post('/appointments', [
            'doctor_id' => $doctor->id,
            'patient_id' => $patientB->id,
            'scheduled_at' => $when->copy()->addMinutes(10)->format('Y-m-d H:i:s'),
            'duration_minutes' => 30,
        ]);

        $response->assertSessionHasErrors('scheduled_at');
        $this->assertSame(1, Appointment::count());
    }

    public function test_requires_patient_id(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $doctor = Doctor::factory()->create();

        $response = $this->actingAs($admin)->post('/appointments', [
            'doctor_id' => $doctor->id,
            'scheduled_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'duration_minutes' => 30,
        ]);

        $response->assertSessionHasErrors('patient_id');
    }
}
