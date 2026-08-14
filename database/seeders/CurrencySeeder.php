<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimals' => 2],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimals' => 2],
            ['code' => 'GBP', 'name' => 'Pound Sterling', 'symbol' => '£', 'decimals' => 2],
            ['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳', 'decimals' => 2],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'decimals' => 2],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$', 'decimals' => 2],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'C$', 'decimals' => 2],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'decimals' => 0],
        ];

        foreach ($currencies as $row) {
            Currency::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'symbol' => $row['symbol'],
                    'decimals' => $row['decimals'],
                    'is_active' => true,
                ],
            );
        }
    }
}
