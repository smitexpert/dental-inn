<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SettingsSeeder::class,
            StockLocationSeeder::class,
            UserSeeder::class,
            ChartOfAccountsSeeder::class,
            ExpenseCategorySeeder::class,
            CurrencySeeder::class,
            ExchangeRateSeeder::class,
            CashDrawerSeeder::class,
            NotificationTemplateSeeder::class,
        ]);
    }
}
