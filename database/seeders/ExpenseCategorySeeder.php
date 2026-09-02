<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            ['name' => 'Rent', 'account_code' => '6000'],
            ['name' => 'Utilities', 'account_code' => '6100'],
            ['name' => 'Salaries', 'account_code' => '6200'],
            ['name' => 'Supplies', 'account_code' => '6300'],
            ['name' => 'Other', 'account_code' => '6900'],
        ];

        foreach ($map as $row) {
            $account = Account::query()->where('code', $row['account_code'])->first();
            if (! $account) {
                continue;
            }

            ExpenseCategory::query()->updateOrCreate(
                ['name' => $row['name']],
                [
                    'account_id' => $account->id,
                    'is_active' => true,
                ],
            );
        }
    }
}
