<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankAccount>
 */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company().' Bank',
            'type' => 'bank',
            'bank_name' => $this->faker->company().' Bank',
            'account_name' => $this->faker->name(),
            'branch' => $this->faker->city(),
            'account_number' => (string) $this->faker->unique()->numerify('##########'),
            'currency_code' => 'BDT',
            'opening_balance' => 0,
            'balance' => 0,
            'is_system' => false,
            'is_active' => true,
        ];
    }

    public function cash(): self
    {
        return $this->state(fn () => [
            'type' => 'cash',
            'bank_name' => null,
            'account_name' => null,
            'branch' => null,
            'account_number' => null,
        ]);
    }

    public function external(): self
    {
        return $this->state(fn () => [
            'type' => 'external',
            'bank_name' => null,
            'account_name' => null,
            'branch' => null,
            'account_number' => null,
        ]);
    }
}
