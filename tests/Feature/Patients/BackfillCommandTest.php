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
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BackfillCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Simulate the pre-sub-project-2 schema on the SQLite test database.
     * The production migration sequence already covers MySQL; this helper
     * gives the test the legacy columns it needs to exercise the backfill.
     */
    protected function addLegacyColumns(): void
    {
        if (! Schema::hasColumn('appointments', 'patient_name')) {
            Schema::table('appointments', function ($table): void {
                $table->string('patient_name', 255)->nullable()->after('patient_id');
            });
        }
        if (! Schema::hasColumn('appointments', 'patient_phone')) {
            Schema::table('appointments', function ($table): void {
                $table->string('patient_phone', 32)->nullable()->after('patient_name');
            });
        }
    }

    public function test_creates_patients_grouped_by_phone(): void
    {
        $this->addLegacyColumns();

        $doctor = Doctor::factory()->create();
        $user = User::factory()->create();

        // 3 appointments with the same phone + similar names
        DB::table('appointments')->insert([
            ['doctor_id' => $doctor->id, 'patient_name' => 'John Smith', 'patient_phone' => '+8801711111111', 'scheduled_at' => now(), 'duration_minutes' => 30, 'status' => AppointmentStatus::Scheduled->value, 'created_by' => $user->id, 'created_at' => now(), 'updated_at' => now()],
            ['doctor_id' => $doctor->id, 'patient_name' => 'John Smith', 'patient_phone' => '+8801711111111', 'scheduled_at' => now(), 'duration_minutes' => 30, 'status' => AppointmentStatus::Scheduled->value, 'created_by' => $user->id, 'created_at' => now(), 'updated_at' => now()],
            ['doctor_id' => $doctor->id, 'patient_name' => 'Johnny Smith', 'patient_phone' => '+8801711111111', 'scheduled_at' => now(), 'duration_minutes' => 30, 'status' => AppointmentStatus::Scheduled->value, 'created_by' => $user->id, 'created_at' => now(), 'updated_at' => now()],
            ['doctor_id' => $doctor->id, 'patient_name' => 'Jane Doe', 'patient_phone' => '+8801722222222', 'scheduled_at' => now(), 'duration_minutes' => 30, 'status' => AppointmentStatus::Scheduled->value, 'created_by' => $user->id, 'created_at' => now(), 'updated_at' => now()],
            ['doctor_id' => $doctor->id, 'patient_name' => 'Bob Brown', 'patient_phone' => null, 'scheduled_at' => now(), 'duration_minutes' => 30, 'status' => AppointmentStatus::Scheduled->value, 'created_by' => $user->id, 'created_at' => now(), 'updated_at' => now()],
            ['doctor_id' => $doctor->id, 'patient_name' => 'Bob Brown', 'patient_phone' => null, 'scheduled_at' => now(), 'duration_minutes' => 30, 'status' => AppointmentStatus::Scheduled->value, 'created_by' => $user->id, 'created_at' => now(), 'updated_at' => now()],
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
        $this->addLegacyColumns();
        $doctor = Doctor::factory()->create();
        $user = User::factory()->create();

        DB::table('appointments')->insert([
            'doctor_id' => $doctor->id,
            'patient_name' => 'Solo Person',
            'patient_phone' => '+8801799999999',
            'scheduled_at' => now(),
            'duration_minutes' => 30,
            'status' => AppointmentStatus::Scheduled->value,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('patients:backfill-from-appointments', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertSame(0, Patient::count());
        $this->assertNull(Appointment::first()->patient_id);
    }

    public function test_assigns_unique_patient_codes(): void
    {
        $this->addLegacyColumns();
        $doctor = Doctor::factory()->create();
        $user = User::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            DB::table('appointments')->insert([
                'doctor_id' => $doctor->id,
                'patient_name' => 'Name '.$i,
                'patient_phone' => '+880170000000'.$i,
                'scheduled_at' => now(),
                'duration_minutes' => 30,
                'status' => AppointmentStatus::Scheduled->value,
                'created_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->artisan('patients:backfill-from-appointments')->assertExitCode(0);

        $codes = Patient::pluck('patient_code')->all();
        $this->assertCount(3, $codes);
        $this->assertCount(3, array_unique($codes));
    }
}
