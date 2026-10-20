<?php

declare(strict_types=1);

namespace Tests\Feature\Patients;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreatePatientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-patients', 'guard_name' => 'web']));
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'manage-patients', 'guard_name' => 'web']));
    }

    public function test_creates_patient_with_auto_code(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->post('/patients', [
            'name' => 'Jane Doe',
            'phone' => '+8801712345678',
            'email' => 'jane@example.com',
        ]);

        $response->assertRedirect();
        $this->assertSame(1, Patient::count());
        $patient = Patient::first();
        $this->assertSame('Jane Doe', $patient->name);
        $this->assertNotEmpty($patient->patient_code);
        $this->assertStringStartsWith('P-'.date('Y').'-', $patient->patient_code);
    }

    public function test_name_required(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->post('/patients', [
            'phone' => '+8801712345678',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_gender_must_be_valid(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->post('/patients', [
            'name' => 'Jane Doe',
            'gender' => 'not_a_gender',
        ]);

        $response->assertSessionHasErrors('gender');
    }

    public function test_receptionist_can_create(): void
    {
        $receptionist = User::factory()->create(['is_active' => true]);
        $role = Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'view-patients', 'guard_name' => 'web']));
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'manage-patients', 'guard_name' => 'web']));
        $receptionist->assignRole('receptionist');

        $response = $this->actingAs($receptionist)->post('/patients', [
            'name' => 'Front Desk Patient',
            'phone' => '+8801799999999',
        ]);

        $response->assertRedirect();
        $this->assertSame(1, Patient::where('name', 'Front Desk Patient')->count());
    }

    public function test_creates_patient_with_age_deriving_date_of_birth(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->post('/patients', [
            'name' => 'Jane Doe',
            'age' => 20,
        ]);

        $response->assertRedirect();

        $patient = Patient::first();
        $this->assertSame(20, $patient->age);
        $this->assertSame(
            now()->subYears(20)->toDateString(),
            $patient->date_of_birth->toDateString(),
        );
    }

    public function test_age_must_be_within_bounds(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->post('/patients', [
            'name' => 'Jane Doe',
            'age' => 999,
        ]);

        $response->assertSessionHasErrors('age');
        $this->assertSame(0, Patient::count());
    }

    public function test_age_must_be_a_whole_number(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->post('/patients', [
            'name' => 'Jane Doe',
            'age' => 'twenty',
        ]);

        $response->assertSessionHasErrors('age');
        $this->assertSame(0, Patient::count());
    }

    public function test_age_is_not_persisted_as_a_column(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $this->actingAs($admin)->post('/patients', [
            'name' => 'Jane Doe',
            'age' => 20,
        ]);

        // `age` is an input-only field: the patients table has no such
        // column, and the repository must never receive it.
        $this->assertArrayNotHasKey(
            'age',
            Patient::first()->getAttributes(),
        );
    }

    public function test_creates_patient_without_age_leaves_date_of_birth_null(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->post('/patients', [
            'name' => 'Jane Doe',
        ]);

        $response->assertRedirect();
        $this->assertNull(Patient::first()->date_of_birth);
        $this->assertNull(Patient::first()->age);
    }
}
