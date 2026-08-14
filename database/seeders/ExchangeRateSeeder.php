<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds baseline cross rates with BDT as the base.
 *
 * We seed `X -> BDT` for every non-base currency, plus the inverse
 * `BDT -> X`. The ExchangeRateService can derive any other pair at
 * lookup time using the inverse fallback so the UI does not need all
 * 56 pairs up front.
 */
class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Approximate baseline rates: 1 unit of `from` = `rate` BDT
        $crossRates = [
            'USD' => 110.0,
            'EUR' => 120.0,
            'GBP' => 140.0,
            'INR' => 1.32,
            'AUD' => 72.0,
            'CAD' => 81.0,
            'JPY' => 0.74,
        ];

        foreach ($crossRates as $code => $toBdt) {
            // X -> BDT
            ExchangeRate::query()->updateOrCreate(
                [
                    'from_currency_code' => $code,
                    'to_currency_code' => 'BDT',
                    'effective_at' => $now,
                ],
                [
                    'rate' => number_format($toBdt, 8, '.', ''),
                    'created_by' => null,
                ],
            );

            // BDT -> X (inverse)
            ExchangeRate::query()->updateOrCreate(
                [
                    'from_currency_code' => 'BDT',
                    'to_currency_code' => $code,
                    'effective_at' => $now,
                ],
                [
                    'rate' => number_format(1.0 / $toBdt, 8, '.', ''),
                    'created_by' => null,
                ],
            );
        }

        // BDT -> BDT placeholder so the pair exists for listing.
        ExchangeRate::query()->updateOrCreate(
            [
                'from_currency_code' => 'BDT',
                'to_currency_code' => 'BDT',
                'effective_at' => $now,
            ],
            [
                'rate' => '1.00000000',
                'created_by' => null,
            ],
        );
    }
}
