<?php

declare(strict_types=1);

namespace App\Enums;

enum BankAccountType: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Bank => 'Bank',
            self::External => 'External',
        };
    }
}
