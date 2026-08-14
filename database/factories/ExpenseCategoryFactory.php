<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'account_id' => Account::factory(),
            'is_active' => true,
        ];
    }
}
