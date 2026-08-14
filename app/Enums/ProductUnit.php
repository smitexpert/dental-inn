<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductUnit: string
{
    case Piece = 'piece';
    case Box = 'box';
    case Vial = 'vial';
    case Syringe = 'syringe';
    case Ml = 'ml';
    case Gram = 'gram';
    case Kg = 'kg';
    case Liter = 'liter';
    case Pack = 'pack';

    public function label(): string
    {
        return match ($this) {
            self::Piece => 'Piece',
            self::Box => 'Box',
            self::Vial => 'Vial',
            self::Syringe => 'Syringe',
            self::Ml => 'Millilitre (ml)',
            self::Gram => 'Gram',
            self::Kg => 'Kilogram',
            self::Liter => 'Litre',
            self::Pack => 'Pack',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Piece => 'pc',
            self::Box => 'box',
            self::Vial => 'vl',
            self::Syringe => 'syr',
            self::Ml => 'ml',
            self::Gram => 'g',
            self::Kg => 'kg',
            self::Liter => 'L',
            self::Pack => 'pk',
        };
    }
}
