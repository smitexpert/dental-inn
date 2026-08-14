<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExchangeRate;
use App\Repositories\Contracts\ExchangeRateRepository;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Authoritative reader + writer for exchange rates.
 *
 * Currencies equal? Rate is 1.0 and no row is needed.
 * Direct pair missing? Try the inverse pair (1 / rate) as a fallback so the
 * UI does not need to seed 9x9 = 81 rows up front.
 */
class ExchangeRateService
{
    public function __construct(private readonly ExchangeRateRepository $rates) {}

    /**
     * Return a rate row for the given pair at $at. If `from === to` returns
     * a synthetic 1.0 rate. Throws when no rate exists.
     */
    public function getRate(string $from, string $to, ?Carbon $at = null): ExchangeRate
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return $this->syntheticRate($from, $to, $at);
        }

        $direct = $this->rates->latestForPair($from, $to, $at);
        if ($direct) {
            return $direct;
        }

        $inverse = $this->rates->latestForPair($to, $from, $at);
        if ($inverse && (float) $inverse->rate !== 0.0) {
            return $this->inverseRateRow($inverse, $from, $to, $at);
        }

        throw new RuntimeException("No exchange rate from {$from} to {$to}.");
    }

    public function getRateOrNull(string $from, string $to, ?Carbon $at = null): ?ExchangeRate
    {
        try {
            return $this->getRate($from, $to, $at);
        } catch (RuntimeException) {
            return null;
        }
    }

    public function setRate(
        string $from,
        string $to,
        string $rate,
        Carbon $effectiveAt,
        ?int $userId = null,
    ): ExchangeRate {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            throw new RuntimeException('Cannot set a rate from a currency to itself.');
        }

        if ((float) $rate <= 0) {
            throw new RuntimeException('Exchange rate must be positive.');
        }

        return $this->rates->create([
            'from_currency_code' => $from,
            'to_currency_code' => $to,
            'rate' => (string) $rate,
            'effective_at' => $effectiveAt,
            'created_by' => $userId,
        ]);
    }

    /**
     * Latest rate for every known pair (including inverse lookups). Returned
     * shape: `[from => [to => rateString]]`.
     *
     * @return array<string, array<string, string>>
     */
    public function latestRates(): array
    {
        /** @var array<int, object{from_currency_code: string, to_currency_code: string, max_at: string}> $pairs */
        $pairs = ExchangeRate::query()
            ->selectRaw('from_currency_code, to_currency_code, max(effective_at) as max_at')
            ->groupBy('from_currency_code', 'to_currency_code')
            ->get();

        $out = [];
        foreach ($pairs as $pair) {
            $row = ExchangeRate::query()
                ->forPair($pair->from_currency_code, $pair->to_currency_code)
                ->where('effective_at', $pair->max_at)
                ->orderByDesc('id')
                ->first();

            if (! $row) {
                continue;
            }

            $out[$row->from_currency_code][$row->to_currency_code] = (string) $row->rate;
        }

        return $out;
    }

    private function syntheticRate(string $from, string $to, ?Carbon $at): ExchangeRate
    {
        $row = new ExchangeRate([
            'from_currency_code' => $from,
            'to_currency_code' => $to,
            'rate' => '1.00000000',
            'effective_at' => $at ?? now(),
        ]);
        $row->id = 0;
        $row->exists = false;

        return $row;
    }

    private function inverseRateRow(ExchangeRate $inverse, string $from, string $to, ?Carbon $at): ExchangeRate
    {
        $row = new ExchangeRate([
            'from_currency_code' => $from,
            'to_currency_code' => $to,
            'rate' => number_format(1.0 / (float) $inverse->rate, 8, '.', ''),
            'effective_at' => $inverse->effective_at,
        ]);
        $row->id = -$inverse->id;
        $row->exists = false;

        return $row;
    }
}
