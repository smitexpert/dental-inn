<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Settings\ClinicSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdateWorkingHoursTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $perm = Permission::firstOrCreate(['name' => 'manage-settings', 'guard_name' => 'web']);
        $admin->givePermissionTo($perm);
    }

    public function test_admin_can_update_working_hours(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($user);

        $response = $this->patch('/settings/working-hours', [
            'working_hours' => [
                'mon' => [['start' => '10:00', 'end' => '16:00']],
                'tue' => [],
                'wed' => [],
                'thu' => [],
                'fri' => [],
                'sat' => [],
                'sun' => [],
            ],
        ]);

        $response->assertRedirect();
        $settings = app(ClinicSettings::class);
        $stored = json_decode($settings->working_hours_json, true);
        $this->assertSame('10:00', $stored['mon'][0]['start']);
        $this->assertSame([], $stored['tue']);
    }
}
