<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Notifications;

use App\Services\Notifications\LogDriver;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogDriverTest extends TestCase
{
    public function test_returns_success_with_provider_id_when_logged(): void
    {
        Log::spy();

        $driver = new LogDriver;

        $result = $driver->send(
            channel: 'sms',
            recipient: '+8801712345678',
            subject: null,
            body: 'Hello world',
            meta: ['foo' => 'bar'],
        );

        $this->assertTrue($result['success']);
        $this->assertNull($result['error']);
        $this->assertNotNull($result['provider_id']);
        $this->assertIsString($result['provider_id']);

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $message === 'notification.sent'
                    && $context['channel'] === 'sms'
                    && $context['recipient'] === '+8801712345678'
                    && $context['body'] === 'Hello world'
                    && $context['meta'] === ['foo' => 'bar']
                    && isset($context['provider_id']);
            });
    }

    public function test_returns_failure_when_logging_throws(): void
    {
        Log::shouldReceive('info')->andThrow(new \RuntimeException('disk full'));

        $driver = new LogDriver;

        $result = $driver->send('sms', '+8801712345678', null, 'body');

        $this->assertFalse($result['success']);
        $this->assertSame('disk full', $result['error']);
        $this->assertNull($result['provider_id']);
    }
}
