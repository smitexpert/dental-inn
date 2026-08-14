<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Notifications;

use App\Enums\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SendControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $receptionist = Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        $doctor = Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);

        foreach (['manage-notification-templates', 'send-notifications', 'view-notification-logs'] as $p) {
            $admin->givePermissionTo(Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']));
            $receptionist->givePermissionTo(Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']));
        }
    }

    public function test_admin_can_view_send_form(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        NotificationTemplate::factory()->create([
            'key' => 'welcome',
            'channel' => NotificationChannel::Sms,
            'is_active' => true,
        ]);

        $this->get('/notifications/send')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Notifications/Send/Create')
                ->has('templates', 1)
            );
    }

    public function test_admin_can_send_direct_notification(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        $this->post('/notifications/send', [
            'channel' => 'sms',
            'recipients' => ['+8801712345678'],
            'subject' => null,
            'body' => 'Hello world',
        ])->assertRedirect('/notifications/send');

        $this->assertSame(1, NotificationLog::query()->count());
    }

    public function test_receptionist_can_send_templated_notification(): void
    {
        $receptionist = User::factory()->create(['is_active' => true])->assignRole('receptionist');
        $this->actingAs($receptionist);

        NotificationTemplate::factory()->create([
            'key' => 'welcome',
            'channel' => NotificationChannel::Sms,
            'is_active' => true,
            'body' => 'Hi {{ name }}',
        ]);

        $this->post('/notifications/send', [
            'channel' => 'sms',
            'template_key' => 'welcome',
            'recipients' => ['+8801712345678'],
            'variables' => ['name' => 'Bob'],
        ])->assertRedirect('/notifications/send');

        $this->assertSame(1, NotificationLog::query()->count());
        $log = NotificationLog::query()->first();
        $this->assertSame('Hi Bob', $log->body_rendered);
    }

    public function test_send_validates_required_fields(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        $this->post('/notifications/send', [])
            ->assertSessionHasErrors(['channel', 'recipients']);
    }

    public function test_doctor_cannot_send_notifications(): void
    {
        $doctor = User::factory()->create(['is_active' => true]);
        $doctor->assignRole('doctor');
        $this->actingAs($doctor);

        $this->post('/notifications/send', [
            'channel' => 'sms',
            'recipients' => ['+8801712345678'],
            'body' => 'Hi',
        ])->assertStatus(403);
    }
}
