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

    public function test_age_updates_date_of_birth(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create(['date_of_birth' => '1990-05-04']);

        $this->actingAs($admin)->patch("/patients/{$patient->id}", [
            'name' => $patient->name,
            'age' => 45,
        ]);

        $patient->refresh();
        $this->assertSame(45, $patient->age);
        $this->assertSame(now()->subYears(45)->toDateString(), $patient->date_of_birth->toDateString());
    }

    public function test_blank_age_preserves_existing_date_of_birth(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create(['date_of_birth' => '1990-05-04']);

        // A blank age means "staff didn't touch the field", not "clear the
        // age" — editing an unrelated field must not wipe the date of birth.
        $this->actingAs($admin)->patch("/patients/{$patient->id}", [
            'name' => 'Renamed Patient',
            'age' => '',
        ]);

        $patient->refresh();
        $this->assertSame('Renamed Patient', $patient->name);
        $this->assertSame('1990-05-04', $patient->date_of_birth->toDateString());
    }

    public function test_omitted_age_preserves_existing_date_of_birth(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create(['date_of_birth' => '1990-05-04']);

        $this->actingAs($admin)->patch("/patients/{$patient->id}", [
            'name' => $patient->name,
            'phone' => '+8801700000000',
        ]);

        $patient->refresh();
        $this->assertSame('1990-05-04', $patient->date_of_birth->toDateString());
    }

    public function test_age_must_be_within_bounds(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create();

        $response = $this->actingAs($admin)->patch("/patients/{$patient->id}", [
            'name' => $patient->name,
            'age' => 999,
        ]);

        $response->assertSessionHasErrors('age');
    }
}
