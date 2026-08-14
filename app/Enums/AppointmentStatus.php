<?php

declare(strict_types=1);

namespace App\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Confirmed => 'Confirmed',
            self::CheckedIn => 'Checked in',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::NoShow => 'No show',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::NoShow], true);
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Scheduled => in_array($next, [self::Confirmed, self::Cancelled, self::NoShow], true),
            self::Confirmed => in_array($next, [self::CheckedIn, self::Cancelled, self::NoShow], true),
            self::CheckedIn => in_array($next, [self::Completed, self::NoShow], true),
            default => false,
        };
    }
}
