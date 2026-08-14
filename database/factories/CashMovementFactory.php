<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CashMovementType;
use App\Models\CashMovement;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashMovement>
 */
class CashMovementFactory extends Factory
{
    protected $model = CashMovement::class;

    public function definition(): array
    {
        return [
            'currency_code' => Currency::factory()->bdt(),
            'type' => CashMovementType::CashIn,
            'amount' => '100.0000',
            'exchange_rate_id' => null,
            'counter_currency_code' => null,
            'counter_amount' => null,
            'reason' => 'Factory deposit',
            'reference_type' => null,
            'reference_id' => null,
            'performed_by' => User::factory(),
            'exchange_group_id' => null,
            'created_at' => now(),
        ];
    }

    public function cashOut(): static
    {
        return $this->state(fn (): array => [
            'type' => CashMovementType::CashOut,
            'amount' => '-50.0000',
            'reason' => 'Factory withdrawal',
        ]);
    }
}
