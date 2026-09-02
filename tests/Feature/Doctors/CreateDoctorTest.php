<?php

declare(strict_types=1);

namespace Tests\Feature\Doctors;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateDoctorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        $perm = Permission::firstOrCreate(['name' => 'manage-doctors', 'guard_name' => 'web']);
        $admin->givePermissionTo($perm);
    }

    public function test_creates_doctor(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->post('/doctors', [
            'name' => 'Dr Smith',
            'email' => 'smith@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'specialization' => 'Orthodontics',
            'license_number' => 'LIC-12345',
            'color' => '#0EA5E9',
            'is_available' => true,
            'is_active' => true,
        ]);

        $response->assertRedirect('/doctors');
        $this->assertDatabaseHas('users', ['email' => 'smith@example.com']);
        $this->assertDatabaseHas('doctors', ['license_number' => 'LIC-12345']);
        $this->assertTrue(User::where('email', 'smith@example.com')->first()->hasRole('doctor'));
    }

    public function test_rejects_invalid_color(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        $response = $this->actingAs($admin)->post('/doctors', [
            'name' => 'Dr Bad',
            'email' => 'bad@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'color' => 'red',
        ]);

        $response->assertSessionHasErrors('color');
    }
}
