<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductCategory: string
{
    case Consumable = 'consumable';
    case Equipment = 'equipment';
    case Medication = 'medication';
    case Material = 'material';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Consumable => 'Consumable',
            self::Equipment => 'Equipment',
            self::Medication => 'Medication',
            self::Material => 'Material',
            self::Other => 'Other',
        };
    }
}
