<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UsersIndexKpisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'manage-users', 'guard_name' => 'web']));
    }

    public function test_index_returns_kpis(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        User::factory()->count(3)->create(['is_active' => true]);

        $response = $this->actingAs($admin)->get('/admin/users');
        $response->assertOk();

        $kpis = $response->viewData('page')['props']['kpis'] ?? [];
        $this->assertCount(4, $kpis);
        $this->assertSame('total', $kpis[0]['id']);
        $this->assertSame((string) User::count(), $kpis[0]['value']);
        $this->assertSame('doctors', $kpis[2]['id']);
    }
}
