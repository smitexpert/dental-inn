<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'contact_person' => fake()->boolean(70) ? fake()->name() : null,
            'phone' => fake()->boolean(80) ? '+1-555-'.fake()->numerify('####') : null,
            'email' => fake()->boolean(70) ? fake()->companyEmail() : null,
            'address' => fake()->boolean(60) ? fake()->streetAddress() : null,
            'notes' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
