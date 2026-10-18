<?php

declare(strict_types=1);

namespace App\Enums;

enum MealRelation: string
{
    case BeforeMeal = 'before_meal';
    case AfterMeal = 'after_meal';
    case WithMeal = 'with_meal';
    case EmptyStomach = 'empty_stomach';
    case NoRelation = 'no_relation';
    case Anytime = 'anytime';

    public function labelBn(): string
    {
        return match ($this) {
            self::BeforeMeal => 'খাবারের আগে',
            self::AfterMeal => 'খাবারের পরে',
            self::WithMeal => 'খাবারের সাথে',
            self::EmptyStomach => 'খালি পেটে',
            self::NoRelation => 'খাবারের সাথে সম্পর্ক নেই',
            self::Anytime => 'যেকোনো সময়',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::BeforeMeal => 'Before meal',
            self::AfterMeal => 'After meal',
            self::WithMeal => 'With meal',
            self::EmptyStomach => 'On empty stomach',
            self::NoRelation => 'No relation to meal',
            self::Anytime => 'Anytime',
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
