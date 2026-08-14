<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CashDrawer;
use App\Models\Currency;
use Illuminate\Database\Seeder;

class CashDrawerSeeder extends Seeder
{
    public function run(): void
    {
        $currencies = Currency::query()->active()->pluck('code');

        foreach ($currencies as $code) {
            CashDrawer::query()->firstOrCreate(
                ['currency_code' => $code],
                ['balance' => '0.0000'],
            );
        }
    }
}
