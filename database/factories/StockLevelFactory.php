<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockLevel>
 */
class StockLevelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'location_id' => StockLocation::factory(),
            'quantity' => fake()->numberBetween(0, 100),
            'last_counted_at' => null,
        ];
    }
}
