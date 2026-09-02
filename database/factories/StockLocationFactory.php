<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StockLocationType;
use App\Models\StockLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockLocation>
 */
class StockLocationFactory extends Factory
{
    public function definition(): array
    {
        $types = array_map(fn (StockLocationType $t) => $t->value, StockLocationType::cases());

        return [
            'name' => fake()->unique()->word().' Storage',
            'location_type' => fake()->randomElement($types),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
