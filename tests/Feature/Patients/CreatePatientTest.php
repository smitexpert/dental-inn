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
}
