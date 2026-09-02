<?php

declare(strict_types=1);

namespace Tests\Feature\Patients;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuickStorePatientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-patients', 'guard_name' => 'web']));
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'manage-patients', 'guard_name' => 'web']));
    }

    public function test_quick_store_creates_patient_and_flashes_resource(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)
            ->from('/appointments/create')
            ->post('/patients/quick-store', [
                'name' => 'Walk-in Patient',
                'phone' => '+8801700000000',
            ]);

        $response->assertRedirect('/appointments/create');
        $this->assertSame(1, Patient::count());

        // The `patient` flash key is what the front-end dialog reads
        // (`page.props.flash.patient`) to attach the new patient to
        // the parent appointment form. It must contain the resource
        // representation (id + patient_code + name + phone).
        $flashedAll = session()->all();
        $this->assertArrayHasKey('patient', $flashedAll);

        $payload = $flashedAll['patient'];
        $this->assertIsArray($payload);
        $this->assertSame('Walk-in Patient', $payload['name']);
        $this->assertSame('+8801700000000', $payload['phone']);
        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('patient_code', $payload);
    }

    public function test_quick_store_requires_name(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)
            ->post('/patients/quick-store', [
                'phone' => '+8801700000000',
            ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(0, Patient::count());
    }

    public function test_quick_store_allows_phone_to_be_optional(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)
            ->from('/appointments/create')
            ->post('/patients/quick-store', [
                'name' => 'Walk-in Patient',
                // phone intentionally omitted — the dialog marks it optional
            ]);

        $response->assertRedirect('/appointments/create');
        $this->assertSame(1, Patient::count());
        $this->assertSame('Walk-in Patient', Patient::first()->name);
        $this->assertNull(Patient::first()->phone);
    }

    public function test_quick_store_derives_date_of_birth_from_age(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)
            ->from('/appointments/create')
            ->post('/patients/quick-store', [
                'name' => 'Walk-in Patient',
                'age' => 30,
            ]);

        $response->assertRedirect('/appointments/create');
        $this->assertSame(1, Patient::count());

        $patient = Patient::first();
        $this->assertSame(30, $patient->age);
        // The exact DOB depends on today's date, so check that the
        // stored year is current year - 30 (give or take 1 day for
        // birthday edge cases).
        $this->assertSame((int) now()->subYears(30)->format('Y'), (int) $patient->date_of_birth->format('Y'));
    }

    public function test_quick_store_age_validation(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)
            ->from('/appointments/create')
            ->post('/patients/quick-store', [
                'name' => 'Walk-in Patient',
                'age' => 999, // out of bounds
            ]);

        $response->assertSessionHasErrors('age');
        $this->assertSame(0, Patient::count());
    }
}
