<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Issued => 'Issued',
            self::PartiallyPaid => 'Partially paid',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Issued => 'info',
            self::PartiallyPaid => 'warning',
            self::Paid => 'success',
            self::Overdue => 'danger',
            self::Cancelled => 'neutral',
            self::Refunded => 'neutral',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Issued, self::PartiallyPaid, self::Overdue], true);
    }

    public function isFinalised(): bool
    {
        return in_array($this, [self::Paid, self::Cancelled, self::Refunded], true);
    }
}
