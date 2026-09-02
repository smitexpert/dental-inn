<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Transfer>
 */
class TransferFactory extends Factory
{
    protected $model = Transfer::class;

    public function definition(): array
    {
        return [
            'from_account_id' => BankAccount::factory(),
            'to_account_id' => BankAccount::factory(),
            'amount' => 100,
            'occurred_at' => now(),
            'reference' => $this->faker->optional()->bothify('REF-####'),
            'note' => $this->faker->optional()->sentence(),
            'transfer_group_id' => (string) Str::uuid(),
            'performed_by' => User::factory(),
        ];
    }
}
