<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $chart = [
            ['code' => '1000', 'name' => 'Cash', 'type' => 'asset'],
            ['code' => '1010', 'name' => 'Bank', 'type' => 'asset'],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => 'asset'],
            ['code' => '1200', 'name' => 'Inventory Asset', 'type' => 'asset'],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability'],
            ['code' => '2100', 'name' => 'Tax Payable', 'type' => 'liability'],
            ['code' => '3000', 'name' => "Owner's Equity", 'type' => 'equity'],
            ['code' => '3100', 'name' => 'Retained Earnings', 'type' => 'equity'],
            ['code' => '4000', 'name' => 'Service Revenue', 'type' => 'revenue'],
            ['code' => '4100', 'name' => 'Product Sales', 'type' => 'revenue'],
            ['code' => '4900', 'name' => 'Other Income', 'type' => 'revenue'],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense'],
            ['code' => '6000', 'name' => 'Rent', 'type' => 'expense'],
            ['code' => '6100', 'name' => 'Utilities', 'type' => 'expense'],
            ['code' => '6200', 'name' => 'Salaries', 'type' => 'expense'],
            ['code' => '6300', 'name' => 'Supplies', 'type' => 'expense'],
            ['code' => '6900', 'name' => 'Other Expenses', 'type' => 'expense'],
        ];

        foreach ($chart as $row) {
            Account::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'is_active' => true,
                ],
            );
        }
    }
}
