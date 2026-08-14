<?php

declare(strict_types=1);

namespace App\Http\Controllers\Exchange;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CashDrawerRepository;
use App\Repositories\Contracts\CashMovementRepository;
use App\Services\ExchangeRateService;
use App\Settings\ClinicSettings;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly CashDrawerRepository $drawers,
        private readonly CashMovementRepository $movements,
        private readonly ExchangeRateService $rates,
    ) {}

    public function index(): Response
    {
        $settings = app(ClinicSettings::class);
        $base = $settings->currency_code;
        $drawers = $this->drawers->all();

        $currencies = $drawers->map(function ($drawer) use ($base): array {
            $code = (string) $drawer->currency_code;
            $balance = (float) $drawer->balance;
            $rate = $this->rates->getRateOrNull($code, $base);
            $converted = $rate ? $balance * (float) $rate->rate : 0.0;

            return [
                'currency_code' => $code,
                'currency' => $drawer->currency ? [
                    'name' => $drawer->currency->name,
                    'symbol' => $drawer->currency->symbol,
                    'decimals' => $drawer->currency->decimals,
                ] : null,
                'balance' => (string) $drawer->balance,
                'balance_in_base' => number_format($converted, 2, '.', ''),
                'last_reconciled_at' => $drawer->last_reconciled_at?->toIso8601String(),
                'last_reconciled_balance' => $drawer->last_reconciled_balance !== null
                    ? (string) $drawer->last_reconciled_balance
                    : null,
                'trend' => $this->movements->dailyNetForCurrency($code, 7),
            ];
        })->values();

        $totalInBase = $currencies->sum(fn (array $row) => (float) $row['balance_in_base']);

        return Inertia::render('Exchange/Dashboard', [
            'currencies' => $currencies,
            'base_currency' => [
                'code' => $base,
                'symbol' => $settings->currency_symbol,
            ],
            'total_in_base' => number_format($totalInBase, 2, '.', ''),
        ]);
    }
}
