<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdatePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    public function test_changes_the_password_when_the_current_one_is_correct(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
            'is_active' => true,
        ])->assignRole('admin');
        $this->actingAs($user);

        $response = $this->patch('/profile/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect();
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_rejects_an_incorrect_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
            'is_active' => true,
        ])->assignRole('admin');
        $this->actingAs($user);

        $response = $this->patch('/profile/password', [
            'current_password' => 'WRONG',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertSessionHasErrors('current_password');
    }
}
