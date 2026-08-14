<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardNotificationsKpiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])
            ->givePermissionTo(Permission::firstOrCreate(['name' => 'view-dashboard', 'guard_name' => 'web']));
    }

    public function test_dashboard_includes_notifications_today_kpi(): void
    {
        NotificationLog::query()->create([
            'channel' => NotificationChannel::Sms->value,
            'recipient' => '+8801712345678',
            'body_rendered' => 'hi',
            'status' => NotificationStatus::Sent->value,
            'created_at' => now(),
        ]);

        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Dashboard')
                ->where('stats.notifications_today.value', 1)
                ->where('stats.notifications_today.label', 'Notifications sent')
            );
    }
}
