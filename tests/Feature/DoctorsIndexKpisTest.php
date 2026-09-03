<?php

declare(strict_types=1);

namespace Tests\Feature\Doctors;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DoctorsIndexKpisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-doctors', 'guard_name' => 'web']));
    }

    public function test_index_returns_kpis(): void
    {
        Doctor::factory()->count(2)->create(['is_available' => true]);

        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->get('/doctors');
        $response->assertOk();

        $kpis = $response->viewData('page')['props']['kpis'] ?? [];
        $this->assertCount(4, $kpis);
        $this->assertSame('total', $kpis[0]['id']);
        $this->assertSame((string) Doctor::count(), $kpis[0]['value']);
        $this->assertSame('available', $kpis[1]['id']);
    }
}
