<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CashDrawer;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashDrawer>
 */
class CashDrawerFactory extends Factory
{
    protected $model = CashDrawer::class;

    public function definition(): array
    {
        return [
            'currency_code' => Currency::factory(),
            'balance' => '0.0000',
            'last_reconciled_at' => null,
            'last_reconciled_balance' => null,
        ];
    }
}
