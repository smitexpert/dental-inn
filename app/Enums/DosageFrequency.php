<?php

declare(strict_types=1);

namespace App\Enums;

enum DosageFrequency: string
{
    case OnceDaily = 'once_daily';
    case TwiceDaily = 'twice_daily';
    case ThreeTimesDaily = 'three_times_daily';
    case FourTimesDaily = 'four_times_daily';
    case Every4Hours = 'every_4_hours';
    case Every6Hours = 'every_6_hours';
    case Every8Hours = 'every_8_hours';
    case AsNeeded = 'as_needed';
    case Custom = 'custom';

    public function labelBn(): string
    {
        return match ($this) {
            self::OnceDaily => 'দিনে ১ বার',
            self::TwiceDaily => 'দিনে ২ বার',
            self::ThreeTimesDaily => 'দিনে ৩ বার',
            self::FourTimesDaily => 'দিনে ৪ বার',
            self::Every4Hours => 'প্রতি ৪ ঘণ্টায়',
            self::Every6Hours => 'প্রতি ৬ ঘণ্টায়',
            self::Every8Hours => 'প্রতি ৮ ঘণ্টায়',
            self::AsNeeded => 'প্রয়োজনে',
            self::Custom => 'কাস্টম',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::OnceDaily => 'Once daily',
            self::TwiceDaily => 'Twice daily',
            self::ThreeTimesDaily => 'Three times daily',
            self::FourTimesDaily => 'Four times daily',
            self::Every4Hours => 'Every 4 hours',
            self::Every6Hours => 'Every 6 hours',
            self::Every8Hours => 'Every 8 hours',
            self::AsNeeded => 'As needed',
            self::Custom => 'Custom',
        };
    }

    /**
     * @return array<int, array{value:string,labelBn:string,labelEn:string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $c): array => [
                'value' => $c->value,
                'labelBn' => $c->labelBn(),
                'labelEn' => $c->labelEn(),
            ],
            self::cases(),
        );
    }
}
