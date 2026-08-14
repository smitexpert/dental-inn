<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    public function test_updates_the_profile(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($user);

        $response = $this->patch('/profile', [
            'name'  => 'New Name',
            'email' => 'new@example.com',
            'phone' => '+8801700000000',
        ]);

        $response->assertRedirect();
        $fresh = $user->fresh();
        $this->assertSame('New Name', $fresh->name);
        $this->assertSame('new@example.com', $fresh->email);
        $this->assertSame('+8801700000000', $fresh->phone);
    }

    public function test_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com', 'is_active' => true]);
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($user);

        $response = $this->patch('/profile', [
            'name'  => 'Me',
            'email' => 'taken@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
