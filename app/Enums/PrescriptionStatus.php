<?php

declare(strict_types=1);

namespace App\Enums;

enum PrescriptionStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Issued => 'Issued',
            self::Cancelled => 'Cancelled',
        };
    }

    public function labelBn(): string
    {
        return match ($this) {
            self::Draft => 'খসড়া',
            self::Issued => 'প্রদত্ত',
            self::Cancelled => 'বাতিল',
        };
    }

    public function isTerminal(): bool
    {
        return $this !== self::Draft;
    }
}
