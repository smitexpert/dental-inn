<?php

declare(strict_types=1);

namespace Tests\Feature\TreatmentRecords;

use App\Enums\TreatmentStatus;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\TreatmentRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IndexTreatmentRecordsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-treatment-records', 'guard_name' => 'web']));
    }

    public function test_index_exposes_serialized_records(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create();
        $doctor = Doctor::factory()->for(User::factory()->create(['is_active' => true, 'name' => 'Dr. Sarah Khan']))->create();
        $record = TreatmentRecord::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'visited_at' => '2026-08-14',
            'chief_complaint' => 'Toothache in upper right',
            'status' => TreatmentStatus::InProgress->value,
        ]);

        $this->actingAs($admin)->get("/patients/{$patient->id}/treatment-records")
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Patients/TreatmentRecords/Index')
                    ->where('records.data.0.id', $record->id)
                    ->where('records.data.0.chief_complaint', 'Toothache in upper right')
                    ->where('records.data.0.doctor_name', 'Dr. Sarah Khan')
                    ->where('records.data.0.status', 'in_progress')
                    ->where('records.data.0.status_label', 'In progress')
                    ->etc(),
            );
    }
}
