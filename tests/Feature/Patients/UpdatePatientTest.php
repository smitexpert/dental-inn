<?php

declare(strict_types=1);

namespace Tests\Feature\Patients;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdatePatientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'manage-patients', 'guard_name' => 'web']));
    }

    public function test_updates_patient(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create(['name' => 'Original']);

        $response = $this->actingAs($admin)->patch("/patients/{$patient->id}", [
            'name' => 'Updated Name',
            'phone' => $patient->phone,
        ]);

        $response->assertRedirect();
        $patient->refresh();
        $this->assertSame('Updated Name', $patient->name);
    }

    public function test_inactive_flag_persists(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create(['is_active' => true]);

        $this->actingAs($admin)->patch("/patients/{$patient->id}", [
            'name' => $patient->name,
            'is_active' => false,
        ]);

        $patient->refresh();
        $this->assertFalse($patient->is_active);
    }
}
