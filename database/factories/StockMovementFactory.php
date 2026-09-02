<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        $types = [
            StockMovementType::Purchase,
            StockMovementType::Consumption,
            StockMovementType::Adjustment,
        ];

        return [
            'product_id' => Product::factory(),
            'location_id' => StockLocation::factory(),
            'type' => fake()->randomElement($types),
            'quantity' => fake()->numberBetween(1, 20),
            'reason' => null,
            'reference_type' => null,
            'reference_id' => null,
            'performed_by' => User::factory(),
            'unit_cost' => null,
            'created_at' => now(),
        ];
    }
}
