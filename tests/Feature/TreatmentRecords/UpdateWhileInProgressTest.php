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

class UpdateWhileInProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-treatment-records', 'guard_name' => 'web']));
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'manage-treatment-records', 'guard_name' => 'web']));
    }

    public function test_updates_in_progress_record(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create();
        $record = TreatmentRecord::factory()->forPatient($patient)->create();

        $response = $this->actingAs($admin)->patch(
            "/patients/{$patient->id}/treatment-records/{$record->id}",
            [
                'visited_at' => now()->format('Y-m-d H:i:s'),
                'chief_complaint' => 'Updated complaint',
                'status' => TreatmentStatus::InProgress->value,
            ],
        );

        $response->assertRedirect();
        $record->refresh();
        $this->assertSame('Updated complaint', $record->chief_complaint);
    }

    public function test_cannot_update_completed_record_via_edit_form(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create();
        $record = TreatmentRecord::factory()->forPatient($patient)->completed()->create();

        $response = $this->actingAs($admin)->get("/patients/{$patient->id}/treatment-records/{$record->id}/edit");
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }
}
