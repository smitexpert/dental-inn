<?php

declare(strict_types=1);

namespace Tests\Feature\Patients;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PatientsIndexKpisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-patients', 'guard_name' => 'web']));
    }

    public function test_index_returns_kpis(): void
    {
        Patient::factory()->count(2)->create();
        Patient::factory()->create(['medical_history' => 'Penicillin allergy']);

        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->get('/patients');
        $response->assertOk();

        $kpis = $response->viewData('page')['props']['kpis'] ?? [];
        $this->assertCount(4, $kpis);
        $this->assertSame('total', $kpis[0]['id']);
        $this->assertSame((string) Patient::count(), $kpis[0]['value']);
        $this->assertSame('active', $kpis[2]['id']);
        $this->assertSame('allergies', $kpis[3]['id']);
    }
}
