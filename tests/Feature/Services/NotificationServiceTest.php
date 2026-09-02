<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Contracts\NotificationDriver;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Patient;
use App\Services\Notifications\LogDriver;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // The container already binds LogDriver; pull the service from the
        // container to ensure the driver is wired through the singleton.
        $this->service = app(NotificationService::class);
    }

    public function test_render_replaces_placeholders(): void
    {
        $template = new NotificationTemplate([
            'key' => 'k',
            'channel' => NotificationChannel::Sms,
            'subject' => 'Hi {{ name }}',
            'body' => 'Hello {{ name }}, your code is {{ code }}',
            'is_active' => true,
            'locale' => 'en',
        ]);
        $template->save();

        $rendered = $this->service->renderTemplate($template, [
            'name' => 'Alice',
            'code' => 12345,
        ]);

        $this->assertSame('Hi Alice', $rendered['subject']);
        $this->assertSame('Hello Alice, your code is 12345', $rendered['body']);
    }

    public function test_render_throws_on_undefined_placeholder(): void
    {
        $template = new NotificationTemplate([
            'key' => 'k',
            'channel' => NotificationChannel::Sms,
            'subject' => null,
            'body' => 'Hi {{ name }}, your code is {{ code }}',
            'is_active' => true,
            'locale' => 'en',
        ]);
        $template->save();

        $this->expectException(\InvalidArgumentException::class);

        $this->service->renderTemplate($template, ['name' => 'Alice']);
    }

    public function test_send_renders_logs_and_marks_success(): void
    {
        NotificationTemplate::factory()->create([
            'key' => 'appointment-confirmation',
            'channel' => NotificationChannel::Sms,
            'subject' => null,
            'body' => 'Hello {{ name }}',
            'is_active' => true,
        ]);

        $results = $this->service->send(
            templateKey: 'appointment-confirmation',
            channelValue: 'sms',
            recipients: ['+8801712345678'],
            variables: ['name' => 'Alice'],
        );

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]['sent']);
        $this->assertTrue($results[0]['logged']);
        $this->assertSame('+8801712345678', $results[0]['recipient']);

        $log = NotificationLog::query()->first();
        $this->assertNotNull($log);
        $this->assertSame(NotificationStatus::Sent, $log->status);
        $this->assertSame('Hello Alice', $log->body_rendered);
        $this->assertSame('sms', $log->channel->value);
        $this->assertNotNull($log->sent_at);
    }

    public function test_send_returns_empty_when_template_missing(): void
    {
        $results = $this->service->send(
            templateKey: 'nope',
            channelValue: 'sms',
            recipients: ['+8801712345678'],
            variables: ['name' => 'Alice'],
        );

        $this->assertSame([], $results);
        $this->assertSame(0, NotificationLog::query()->count());
    }

    public function test_send_marks_invalid_recipient_as_failure(): void
    {
        NotificationTemplate::factory()->create([
            'key' => 'k',
            'channel' => NotificationChannel::Sms,
            'body' => 'Hello',
            'is_active' => true,
        ]);

        $results = $this->service->send(
            templateKey: 'k',
            channelValue: 'sms',
            recipients: ['not-a-phone'],
            variables: [],
        );

        $this->assertCount(1, $results);
        $this->assertFalse($results[0]['logged']);
        $this->assertStringContainsString('Invalid recipient', $results[0]['error']);
    }

    public function test_idempotency_skips_second_send_same_day(): void
    {
        NotificationTemplate::factory()->create([
            'key' => 'k',
            'channel' => NotificationChannel::Sms,
            'body' => 'Hello',
            'is_active' => true,
        ]);

        $patient = Patient::factory()->create();

        $first = $this->service->send(
            templateKey: 'k',
            channelValue: 'sms',
            recipients: ['+8801712345678'],
            variables: [],
            reference: $patient,
        );
        $this->assertTrue($first[0]['sent']);

        $second = $this->service->send(
            templateKey: 'k',
            channelValue: 'sms',
            recipients: ['+8801712345678'],
            variables: [],
            reference: $patient,
        );

        $this->assertSame('already_sent_today', $second[0]['skipped']);
        $this->assertSame(1, NotificationLog::query()->count());
    }

    public function test_send_direct_bypasses_template_lookup(): void
    {
        $results = $this->service->sendDirect(
            channelValue: 'sms',
            recipients: ['+8801712345678'],
            subject: null,
            body: 'ad-hoc body',
        );

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]['sent']);

        $log = NotificationLog::query()->first();
        $this->assertNull($log->template_id);
        $this->assertSame('ad-hoc body', $log->body_rendered);
    }

    public function test_driver_failure_persists_status_failed(): void
    {
        NotificationTemplate::factory()->create([
            'key' => 'k',
            'channel' => NotificationChannel::Sms,
            'body' => 'Hello',
            'is_active' => true,
        ]);

        $failingDriver = new class implements NotificationDriver
        {
            public function send(string $channel, string $recipient, ?string $subject, string $body, array $meta = []): array
            {
                return ['success' => false, 'error' => 'gateway down', 'provider_id' => null];
            }
        };

        $service = new NotificationService($failingDriver);

        $service->send(
            templateKey: 'k',
            channelValue: 'sms',
            recipients: ['+8801712345678'],
            variables: [],
        );

        $log = NotificationLog::query()->first();
        $this->assertNotNull($log);
        $this->assertSame(NotificationStatus::Failed, $log->status);
        $this->assertSame('gateway down', $log->error_message);
        $this->assertNull($log->sent_at);
    }

    public function test_log_driver_is_the_default_driver(): void
    {
        $this->assertInstanceOf(LogDriver::class, app(NotificationDriver::class));
    }
}
