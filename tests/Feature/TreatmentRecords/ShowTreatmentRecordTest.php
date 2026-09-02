<?php

declare(strict_types=1);

namespace Tests\Feature\TreatmentRecords;

use App\Models\Patient;
use App\Models\TreatmentRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShowTreatmentRecordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-treatment-records', 'guard_name' => 'web']));
    }

    public function test_shows_treatment_record(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create();
        $record = TreatmentRecord::factory()->forPatient($patient)->create();

        $response = $this->actingAs($admin)->get("/patients/{$patient->id}/treatment-records/{$record->id}");
        $response->assertOk();
        $response->assertSee($record->chief_complaint);
    }

    public function test_returns_404_for_mismatched_patient(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patientA = Patient::factory()->create();
        $patientB = Patient::factory()->create();
        $record = TreatmentRecord::factory()->forPatient($patientA)->create();

        $response = $this->actingAs($admin)->get("/patients/{$patientB->id}/treatment-records/{$record->id}");
        $response->assertStatus(404);
    }
}
