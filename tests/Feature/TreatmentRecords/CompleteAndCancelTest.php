<?php

declare(strict_types=1);

namespace Tests\Feature\TreatmentRecords;

use App\Enums\TreatmentStatus;
use App\Models\Patient;
use App\Models\TreatmentRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompleteAndCancelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-treatment-records', 'guard_name' => 'web']));
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'manage-treatment-records', 'guard_name' => 'web']));
    }

    public function test_complete_marks_record_completed(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create();
        $record = TreatmentRecord::factory()->forPatient($patient)->create();

        $response = $this->actingAs($admin)->post(
            "/patients/{$patient->id}/treatment-records/{$record->id}/complete",
        );

        $response->assertRedirect();
        $record->refresh();
        $this->assertSame(TreatmentStatus::Completed, $record->status);
    }

    public function test_cancel_marks_record_cancelled(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create();
        $record = TreatmentRecord::factory()->forPatient($patient)->create();

        $response = $this->actingAs($admin)->post(
            "/patients/{$patient->id}/treatment-records/{$record->id}/cancel",
        );

        $response->assertRedirect();
        $record->refresh();
        $this->assertSame(TreatmentStatus::Cancelled, $record->status);
    }
}
