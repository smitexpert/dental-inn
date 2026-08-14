<?php

declare(strict_types=1);

namespace App\Enums;

enum StockLocationType: string
{
    case Storage = 'storage';
    case Operatory = 'operatory';
    case Cabinet = 'cabinet';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Storage => 'Storage',
            self::Operatory => 'Operatory',
            self::Cabinet => 'Cabinet',
            self::Other => 'Other',
        };
    }
}
