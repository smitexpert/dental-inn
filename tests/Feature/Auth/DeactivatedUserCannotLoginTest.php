<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeactivatedUserCannotLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    public function test_rejects_login_for_a_deactivated_user(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
            'is_active' => false,
        ])->assignRole('admin');

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_signs_out_a_deactivated_user_who_is_currently_logged_in(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($user);

        $user->update(['is_active' => false]);

        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
