<?php

declare(strict_types=1);

namespace Tests\Feature\Patients;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PatientSearchApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-patients', 'guard_name' => 'web']));
    }

    public function test_search_returns_matching_patients(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        Patient::factory()->create(['name' => 'John Smith', 'phone' => '+8801711111111']);
        Patient::factory()->create(['name' => 'Jane Doe', 'phone' => '+8801722222222']);

        $response = $this->actingAs($admin)->getJson('/patients/api/search?q=John');
        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame('John Smith', $data[0]['name']);
    }

    public function test_search_limit_respected(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        for ($i = 0; $i < 5; $i++) {
            Patient::factory()->create(['name' => 'Person '.$i]);
        }

        $response = $this->actingAs($admin)->getJson('/patients/api/search?limit=3');
        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_search_empty_term_returns_recent_patients(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        Patient::factory()->count(3)->create();

        $response = $this->actingAs($admin)->getJson('/patients/api/search?q=');
        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }
}
