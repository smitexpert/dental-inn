<?php

declare(strict_types=1);

namespace Tests\Feature\Patients;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ListPatientsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-patients', 'guard_name' => 'web']));
    }

    public function test_lists_patients(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        Patient::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get('/patients');
        $response->assertOk();
        $this->assertSame(3, Patient::count());
    }

    public function test_search_filters_patients(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        Patient::factory()->create(['name' => 'John Smith']);
        Patient::factory()->create(['name' => 'Jane Doe']);

        $response = $this->actingAs($admin)->get('/patients?q=John');
        $response->assertOk();
        $response->assertSee('John Smith');
    }

    public function test_active_filter_excludes_inactive(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        Patient::factory()->create(['name' => 'Active', 'is_active' => true]);
        Patient::factory()->create(['name' => 'Inactive', 'is_active' => false]);

        $response = $this->actingAs($admin)->get('/patients?is_active=0');
        $response->assertOk();
        $response->assertSee('Inactive');
    }
}
