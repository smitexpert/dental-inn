<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Notifications;

use App\Enums\NotificationChannel;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $doctor = Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);

        foreach (['manage-notification-templates', 'view-notification-logs', 'send-notifications'] as $p) {
            $perm = Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            $admin->givePermissionTo($perm);
        }
        $doctor->givePermissionTo(Permission::firstOrCreate(['name' => 'view-notification-logs', 'guard_name' => 'web']));
    }

    public function test_admin_can_view_template_index(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        NotificationTemplate::factory()->create([
            'key' => 'welcome-sms',
            'channel' => NotificationChannel::Sms,
        ]);

        $this->get('/notifications/templates')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Notifications/Templates/Index')
                ->has('templates.data', 1)
            );
    }

    public function test_doctor_can_view_template_index(): void
    {
        $doctor = User::factory()->create(['is_active' => true])->assignRole('doctor');
        $this->actingAs($doctor);

        $this->get('/notifications/templates')->assertOk();
    }

    public function test_admin_can_create_template(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        $response = $this->post('/notifications/templates', [
            'key' => 'appt-confirmation',
            'channel' => 'email',
            'subject' => 'Your appointment is confirmed',
            'body' => 'Hi {{ patient_name }}, your appointment is at {{ time }}.',
            'is_active' => true,
            'locale' => 'en',
        ]);

        $response->assertRedirect('/notifications/templates');
        $this->assertDatabaseHas('notification_templates', ['key' => 'appt-confirmation']);
    }

    public function test_doctor_cannot_create_template(): void
    {
        $doctor = User::factory()->create(['is_active' => true])->assignRole('doctor');
        $this->actingAs($doctor);

        $this->post('/notifications/templates', [
            'key' => 'appt-confirmation',
            'channel' => 'email',
            'body' => 'Hi',
            'locale' => 'en',
        ])->assertStatus(403);
    }

    public function test_admin_can_update_template(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        $template = NotificationTemplate::factory()->create([
            'key' => 'reminder',
            'channel' => NotificationChannel::Sms,
            'body' => 'Old body',
        ]);

        $this->patch('/notifications/templates/'.$template->id, [
            'channel' => 'sms',
            'body' => 'New body',
            'is_active' => true,
            'locale' => 'en',
        ])->assertRedirect('/notifications/templates');

        $template->refresh();
        $this->assertSame('New body', $template->body);
    }

    public function test_admin_can_delete_template(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        $template = NotificationTemplate::factory()->create();

        $this->delete('/notifications/templates/'.$template->id)
            ->assertRedirect('/notifications/templates');

        $this->assertDatabaseMissing('notification_templates', ['id' => $template->id]);
    }

    public function test_preview_returns_rendered_template(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $this->actingAs($admin);

        $template = NotificationTemplate::factory()->create([
            'channel' => NotificationChannel::Sms,
            'subject' => null,
            'body' => 'Hi {{ name }}',
        ]);

        $this->post('/notifications/templates/'.$template->id.'/preview', [
            'variables' => ['name' => 'Alice'],
        ])->assertOk()->assertJson(['body' => 'Hi Alice', 'subject' => null]);
    }
}
