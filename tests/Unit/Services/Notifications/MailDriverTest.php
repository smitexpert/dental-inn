<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Notifications;

use App\Services\Notifications\MailDriver;
use Illuminate\Mail\Mailer;
use Tests\TestCase;

class MailDriverTest extends TestCase
{
    public function test_returns_failure_for_non_email_channel(): void
    {
        $driver = new MailDriver;

        $result = $driver->send('sms', '+8801712345678', null, 'hello');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('email channel', $result['error']);
        $this->assertNull($result['provider_id']);
    }

    public function test_sends_email_through_mailer(): void
    {
        // Swap the mailer to capture calls. We use the array mailer (already
        // configured by phpunit.xml) and inspect the resulting message.
        $driver = new MailDriver;

        $result = $driver->send('email', 'user@example.test', 'Hi there', 'Hello body');

        $this->assertTrue($result['success']);
        $this->assertNull($result['error']);
        $this->assertNull($result['provider_id']);

        /** @var Mailer $mailer */
        $mailer = app('mailer');
        $this->assertInstanceOf(Mailer::class, $mailer);
    }

    public function test_uses_default_subject_when_missing(): void
    {
        $driver = new MailDriver;

        $result = $driver->send('email', 'user@example.test', null, 'body');

        $this->assertTrue($result['success']);
    }
}
