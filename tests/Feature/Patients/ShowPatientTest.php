<?php

declare(strict_types=1);

namespace Tests\Feature\Patients;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShowPatientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-patients', 'guard_name' => 'web']));
    }

    public function test_shows_patient_profile(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        // Use a fixed name without apostrophes so assertSee isn't tripped up
        // by Inertia's JSON page payload (which embeds the raw name) versus
        // assertSee's HTML-entity-escaped search term.
        $patient = Patient::factory()->create(['name' => 'Test Patient']);

        $response = $this->actingAs($admin)->get("/patients/{$patient->id}");
        $response->assertOk();
        $response->assertSee($patient->name);
    }

    public function test_shows_duplicates_when_phone_matches(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $phone = '+8801711111111';
        $patient1 = Patient::factory()->withPhone($phone)->create(['name' => 'Alpha']);
        $patient2 = Patient::factory()->withPhone($phone)->create(['name' => 'Beta']);

        $response = $this->actingAs($admin)->get("/patients/{$patient1->id}");
        $response->assertOk();
        $response->assertSee('Beta');
    }
}
