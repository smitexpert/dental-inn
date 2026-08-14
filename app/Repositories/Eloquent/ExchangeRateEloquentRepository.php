<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ExchangeRate;
use App\Repositories\Contracts\ExchangeRateRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class ExchangeRateEloquentRepository implements ExchangeRateRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $q = ExchangeRate::query()->with(['fromCurrency', 'toCurrency', 'creator']);

        if (! empty($filters['from'])) {
            $q->where('from_currency_code', strtoupper((string) $filters['from']));
        }

        if (! empty($filters['to'])) {
            $q->where('to_currency_code', strtoupper((string) $filters['to']));
        }

        if (! empty($filters['effective_at'])) {
            $q->whereDate('effective_at', $filters['effective_at']);
        }

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $q->where(function (Builder $w) use ($term): void {
                $w->where('from_currency_code', 'like', $term)
                    ->orWhere('to_currency_code', 'like', $term);
            });
        }

        return $q->orderByDesc('effective_at')->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function findByIdOrFail(int $id): ExchangeRate
    {
        return ExchangeRate::query()->findOrFail($id);
    }

    public function latestForPair(string $from, string $to, ?Carbon $at = null): ?ExchangeRate
    {
        return ExchangeRate::query()
            ->forPair(strtoupper($from), strtoupper($to))
            ->effectiveAtOrBefore($at ?? now())
            ->orderByDesc('effective_at')
            ->orderByDesc('id')
            ->first();
    }

    public function create(array $data): ExchangeRate
    {
        $data['from_currency_code'] = strtoupper((string) $data['from_currency_code']);
        $data['to_currency_code'] = strtoupper((string) $data['to_currency_code']);

        $rate = ExchangeRate::query()->updateOrCreate(
            [
                'from_currency_code' => $data['from_currency_code'],
                'to_currency_code' => $data['to_currency_code'],
                'effective_at' => $data['effective_at'],
            ],
            [
                'rate' => (string) $data['rate'],
                'created_by' => $data['created_by'] ?? null,
            ],
        );

        return $rate;
    }

    public function update(ExchangeRate $rate, array $data): ExchangeRate
    {
        $rate->fill($data);
        $rate->save();

        return $rate;
    }

    public function delete(ExchangeRate $rate): void
    {
        $rate->delete();
    }
}
