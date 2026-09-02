<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductCategory;
use App\Enums\ProductUnit;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $categories = array_map(fn (ProductCategory $c) => $c->value, ProductCategory::cases());
        $units = array_map(fn (ProductUnit $u) => $u->value, ProductUnit::cases());

        return [
            'sku' => 'SKU-'.strtoupper(fake()->bothify('??##??')),
            'barcode' => null,
            'name' => ucfirst(fake()->words(2, true)),
            'category' => fake()->randomElement($categories),
            'unit' => fake()->randomElement($units),
            'cost_price' => fake()->randomElement([10, 25, 50, 100, 250]),
            'selling_price' => fake()->randomElement([15, 35, 70, 150, 350]),
            'reorder_level' => 5,
            'reorder_quantity' => 20,
            'description' => null,
            'is_active' => true,
            'track_stock' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function notTracked(): static
    {
        return $this->state(fn (): array => ['track_stock' => false]);
    }

    public function withReorder(int $level, int $quantity): static
    {
        return $this->state(fn (): array => [
            'reorder_level' => $level,
            'reorder_quantity' => $quantity,
        ]);
    }
}
