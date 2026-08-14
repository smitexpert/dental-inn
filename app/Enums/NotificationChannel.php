<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationChannel: string
{
    case Sms = 'sms';
    case Whatsapp = 'whatsapp';
    case Email = 'email';

    public function label(): string
    {
        return match ($this) {
            self::Sms => 'SMS',
            self::Whatsapp => 'WhatsApp',
            self::Email => 'Email',
        };
    }

    public function supportsSubject(): bool
    {
        return $this === self::Email;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }
}
