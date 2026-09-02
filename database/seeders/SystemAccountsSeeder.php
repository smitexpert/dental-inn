<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Settings\ClinicSettings;
use Illuminate\Database\Seeder;

class SystemAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $currency = (string) (app(ClinicSettings::class)->currency_code ?? 'BDT');

        $this->upsertSystem('Main Cash', $currency);
        $this->upsertSystem('External Adjustment', $currency);
    }

    private function upsertSystem(string $name, string $currency): void
    {
        $existing = BankAccount::query()->where('name', $name)->where('is_system', true)->first();
        if ($existing) {
            return;
        }

        $type = $name === 'Main Cash' ? 'cash' : 'external';

        BankAccount::query()->create([
            'name' => $name,
            'type' => $type,
            'currency_code' => $currency,
            'opening_balance' => 0,
            'balance' => 0,
            'is_system' => true,
            'is_active' => true,
        ]);
    }
}
