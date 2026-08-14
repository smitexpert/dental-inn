<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(3)),
            'name' => fake()->unique()->word().' Dollar',
            'symbol' => '$',
            'decimals' => 2,
            'is_active' => true,
        ];
    }

    public function bdt(): static
    {
        return $this->state(fn (): array => [
            'code' => 'BDT',
            'name' => 'Bangladeshi Taka',
            'symbol' => '৳',
            'decimals' => 2,
        ]);
    }

    public function usd(): static
    {
        return $this->state(fn (): array => [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimals' => 2,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
