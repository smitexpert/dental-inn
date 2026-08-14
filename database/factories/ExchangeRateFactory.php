<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    protected $model = ExchangeRate::class;

    public function definition(): array
    {
        return [
            'from_currency_code' => Currency::factory()->bdt(),
            'to_currency_code' => Currency::factory()->usd(),
            'rate' => '0.00909091',
            'effective_at' => now(),
            'created_by' => User::factory(),
        ];
    }
}
