<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CashDrawer;
use App\Models\LedgerEntry;
use App\Services\ExchangeRateService;
use App\Settings\ClinicSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Compare physical cash drawer balances (per currency) against the
 * 1000 Cash ledger account, both expressed in the clinic's base
 * currency, and print any drift. Read-only — does not write to the DB.
 */
class ExchangeReconcileCashVsLedger extends Command
{
    protected $signature = 'exchange:reconcile-cash-vs-ledger';

    protected $description = 'Compare cash drawer balances against the Cash ledger account, converted to base currency.';

    public function handle(ExchangeRateService $rates): int
    {
        $settings = app(ClinicSettings::class);
        $base = (string) $settings->currency_code;

        $drawers = CashDrawer::query()->get();
        $cashAccountId = DB::table('accounts')->where('code', '1000')->value('id');
        $ledgerTotal = $cashAccountId
            ? (float) LedgerEntry::query()->where('debit_account_id', $cashAccountId)->sum('amount')
                - (float) LedgerEntry::query()->where('credit_account_id', $cashAccountId)->sum('amount')
            : 0.0;

        $drawerInBase = 0.0;
        $rows = [];
        foreach ($drawers as $drawer) {
            $code = (string) $drawer->currency_code;
            $balance = (float) $drawer->balance;

            if ($code === $base) {
                $converted = $balance;
            } else {
                $rate = $rates->getRateOrNull($code, $base);
                $converted = $rate ? $balance * (float) $rate->rate : 0.0;
            }

            $drawerInBase += $converted;
            $rows[] = [
                $code,
                number_format($balance, 4, '.', ''),
                number_format($converted, 4, '.', ''),
            ];
        }

        $this->table(['Currency', 'Drawer balance', "Drawer in {$base}"], $rows);

        $diff = $drawerInBase - $ledgerTotal;
        $status = abs($diff) < 0.01 ? 'MATCH' : 'DRIFT';

        $this->line("Drawer total in {$base}: ".number_format($drawerInBase, 4, '.', ''));
        $this->line("Cash ledger ({$base}): ".number_format($ledgerTotal, 4, '.', ''));
        $this->line('Variance: '.number_format($diff, 4, '.', ''));
        $this->line("Status: {$status}");

        return self::SUCCESS;
    }
}
