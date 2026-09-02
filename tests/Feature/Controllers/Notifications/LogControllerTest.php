<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $doctor = Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);

        foreach (['view-notification-logs'] as $p) {
            $admin->givePermissionTo(Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']));
            $doctor->givePermissionTo(Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']));
        }
    }

    public function test_admin_can_view_logs_index(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        NotificationLog::query()->create([
            'channel' => NotificationChannel::Sms->value,
            'recipient' => '+8801712345678',
            'body_rendered' => 'Hi',
            'status' => NotificationStatus::Sent->value,
            'created_at' => now(),
        ]);

        $this->get('/notifications/logs')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Notifications/Logs/Index')
                ->has('logs.data', 1)
            );
    }

    public function test_doctor_can_view_logs_index(): void
    {
        $doctor = User::factory()->create(['is_active' => true])->assignRole('doctor');
        $this->actingAs($doctor);

        $this->get('/notifications/logs')->assertOk();
    }

    public function test_logs_index_filters_by_channel(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        NotificationLog::query()->create([
            'channel' => NotificationChannel::Sms->value,
            'recipient' => '+8801711111111',
            'body_rendered' => 'a',
            'status' => NotificationStatus::Sent->value,
            'created_at' => now(),
        ]);
        NotificationLog::query()->create([
            'channel' => NotificationChannel::Email->value,
            'recipient' => 'a@b.com',
            'body_rendered' => 'b',
            'status' => NotificationStatus::Sent->value,
            'created_at' => now(),
        ]);

        $this->get('/notifications/logs?channel=sms')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('logs.data', 1)
            );
    }
}
