<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    public function test_logs_in_a_valid_user_and_redirects_to_dashboard(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
            'is_active' => true,
        ])->assignRole('admin');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_rejects_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'x@y.test', 'is_active' => true]);

        $response = $this->post('/login', [
            'email' => 'x@y.test',
            'password' => 'wrong',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_rate_limits_after_5_failed_attempts(): void
    {
        User::factory()->create(['email' => 'rl@y.test', 'is_active' => true]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'rl@y.test', 'password' => 'wrong']);
        }

        $response = $this->post('/login', ['email' => 'rl@y.test', 'password' => 'wrong']);
        $response->assertSessionHasErrors('email');
    }
}
