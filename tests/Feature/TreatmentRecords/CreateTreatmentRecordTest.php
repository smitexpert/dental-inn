<?php

declare(strict_types=1);

namespace Tests\Feature\TreatmentRecords;

use App\Enums\TreatmentStatus;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\TreatmentRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateTreatmentRecordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-treatment-records', 'guard_name' => 'web']));
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'manage-treatment-records', 'guard_name' => 'web']));
    }

    public function test_creates_treatment_record(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create();
        $doctor = Doctor::factory()->create();

        $response = $this->actingAs($admin)->post("/patients/{$patient->id}/treatment-records", [
            'doctor_id' => $doctor->id,
            'visited_at' => now()->format('Y-m-d H:i:s'),
            'chief_complaint' => 'Tooth pain',
            'status' => TreatmentStatus::InProgress->value,
        ]);

        $response->assertRedirect();
        $this->assertSame(1, TreatmentRecord::count());
        $record = TreatmentRecord::first();
        $this->assertSame($patient->id, $record->patient_id);
        $this->assertSame($doctor->id, $record->doctor_id);
    }

    public function test_visited_at_required(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create();

        $response = $this->actingAs($admin)->post("/patients/{$patient->id}/treatment-records", [
            'chief_complaint' => 'Tooth pain',
        ]);

        $response->assertSessionHasErrors('visited_at');
    }

    public function test_tooth_chart_array_accepted(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create();

        $response = $this->actingAs($admin)->post("/patients/{$patient->id}/treatment-records", [
            'visited_at' => now()->format('Y-m-d H:i:s'),
            'tooth_chart' => [
                'teeth' => [
                    [
                        'fdi' => 16,
                        'procedures' => [
                            ['code' => 'D0140', 'name' => 'Limited oral evaluation', 'surface' => 'occlusal'],
                        ],
                    ],
                ],
            ],
            'status' => TreatmentStatus::InProgress->value,
        ]);

        $response->assertRedirect();
        $record = TreatmentRecord::first();
        $this->assertSame(16, $record->tooth_chart['teeth'][0]['fdi']);
    }
}
