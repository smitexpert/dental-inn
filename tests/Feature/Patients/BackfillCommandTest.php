<?php

declare(strict_types=1);

namespace Tests\Feature\Patients;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BackfillCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_patients_grouped_by_phone(): void
    {
        $doctor = Doctor::factory()->create();
        $user = User::factory()->create();

        // 3 appointments with the same phone + similar names
        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_name' => 'John Smith',
            'patient_phone' => '+8801711111111',
            'created_by' => $user->id,
        ]);
        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_name' => 'John Smith',
            'patient_phone' => '+8801711111111',
            'created_by' => $user->id,
        ]);
        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_name' => 'Johnny Smith',
            'patient_phone' => '+8801711111111',
            'created_by' => $user->id,
        ]);
        // Different phone
        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_name' => 'Jane Doe',
            'patient_phone' => '+8801722222222',
            'created_by' => $user->id,
        ]);
        // No phone, same name as another
        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_name' => 'Bob Brown',
            'patient_phone' => null,
            'created_by' => $user->id,
        ]);
        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_name' => 'Bob Brown',
            'patient_phone' => null,
            'created_by' => $user->id,
        ]);

        $this->artisan('patients:backfill-from-appointments')->assertExitCode(0);

        $this->assertSame(3, Patient::count());
        $this->assertSame(6, Appointment::whereNotNull('patient_id')->count());

        $phone1 = Patient::where('phone', '+8801711111111')->first();
        $this->assertNotNull($phone1);
        $this->assertSame(3, Appointment::where('patient_id', $phone1->id)->count());

        $phone2 = Patient::where('phone', '+8801722222222')->first();
        $this->assertNotNull($phone2);
        $this->assertSame(1, Appointment::where('patient_id', $phone2->id)->count());

        $bob = Patient::where('name', 'Bob Brown')->whereNull('phone')->first();
        $this->assertNotNull($bob);
        $this->assertSame(2, Appointment::where('patient_id', $bob->id)->count());
    }

    public function test_dry_run_does_not_write(): void
    {
        $doctor = Doctor::factory()->create();
        $user = User::factory()->create();

        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_name' => 'Solo Person',
            'patient_phone' => '+8801799999999',
            'created_by' => $user->id,
        ]);

        $this->artisan('patients:backfill-from-appointments', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertSame(0, Patient::count());
        $this->assertNull(Appointment::first()->patient_id);
    }

    public function test_assigns_unique_patient_codes(): void
    {
        $doctor = Doctor::factory()->create();
        $user = User::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            Appointment::factory()->create([
                'doctor_id' => $doctor->id,
                'patient_name' => 'Name '.$i,
                'patient_phone' => '+880170000000'.$i,
                'created_by' => $user->id,
            ]);
        }

        $this->artisan('patients:backfill-from-appointments')->assertExitCode(0);

        $codes = Patient::pluck('patient_code')->all();
        $this->assertCount(3, $codes);
        $this->assertCount(3, array_unique($codes));
    }
}
