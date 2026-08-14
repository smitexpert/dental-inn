<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use PHPUnit\Framework\TestCase;

class NotificationEnumsTest extends TestCase
{
    public function test_notification_channel_values(): void
    {
        $this->assertSame(['sms', 'whatsapp', 'email'], NotificationChannel::values());
    }

    public function test_email_supports_subject(): void
    {
        $this->assertTrue(NotificationChannel::Email->supportsSubject());
        $this->assertFalse(NotificationChannel::Sms->supportsSubject());
        $this->assertFalse(NotificationChannel::Whatsapp->supportsSubject());
    }

    public function test_notification_status_values(): void
    {
        $this->assertSame(['queued', 'sent', 'failed', 'bounced'], NotificationStatus::values());
    }

    public function test_notification_status_colors(): void
    {
        $this->assertSame('success', NotificationStatus::Sent->color());
        $this->assertSame('danger', NotificationStatus::Failed->color());
    }
}
