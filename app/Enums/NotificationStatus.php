<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
    case Bounced = 'bounced';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Queued',
            self::Sent => 'Sent',
            self::Failed => 'Failed',
            self::Bounced => 'Bounced',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Queued => 'neutral',
            self::Sent => 'success',
            self::Failed => 'danger',
            self::Bounced => 'warning',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $s): string => $s->value, self::cases());
    }
}
