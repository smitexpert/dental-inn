<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesIndexKpisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'manage-roles', 'guard_name' => 'web']));
    }

    public function test_index_returns_kpis(): void
    {
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view-patients', 'guard_name' => 'web']);

        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->get('/admin/roles');
        $response->assertOk();

        $kpis = $response->viewData('page')['props']['kpis'] ?? [];
        $this->assertCount(3, $kpis);
        $this->assertSame('roles', $kpis[0]['id']);
        $this->assertSame((string) Role::count(), $kpis[0]['value']);
        $this->assertSame('permissions', $kpis[1]['id']);
    }
}
