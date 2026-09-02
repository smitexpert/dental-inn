<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'expense_date' => now()->toDateString(),
            'category_id' => ExpenseCategory::factory(),
            'amount' => fake()->randomFloat(2, 50, 2000),
            'currency_code' => 'BDT',
            'exchange_rate' => 1.0,
            'payment_method' => PaymentMethod::Cash->value,
            'vendor' => fake()->company(),
            'description' => fake()->sentence(),
            'status' => ExpenseStatus::Pending->value,
            'created_by' => User::factory(),
        ];
    }
}
