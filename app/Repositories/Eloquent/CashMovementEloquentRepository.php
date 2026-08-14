<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\CashMovementType;
use App\Models\CashMovement;
use App\Repositories\Contracts\CashMovementRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class CashMovementEloquentRepository implements CashMovementRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $q = CashMovement::query()
            ->with(['currency', 'counterCurrency', 'performer', 'exchangeRate']);

        if (! empty($filters['currency_code'])) {
            $q->where('currency_code', strtoupper((string) $filters['currency_code']));
        }

        if (! empty($filters['type'])) {
            $q->where('type', $filters['type']);
        }

        if (! empty($filters['date_from'])) {
            $q->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $q->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $q->where(function (Builder $w) use ($term): void {
                $w->where('reason', 'like', $term)
                    ->orWhere('reference_type', 'like', $term);
            });
        }

        return $q->orderByDesc('created_at')->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function findByIdOrFail(int $id): CashMovement
    {
        return CashMovement::query()->findOrFail($id);
    }

    public function sumForCurrencySince(string $code, Carbon $since): string
    {
        $sum = CashMovement::query()
            ->forCurrency(strtoupper($code))
            ->where('created_at', '>=', $since)
            ->sum('amount');

        return (string) $sum;
    }

    /**
     * @return array<string, string>  ISO date => signed total
     */
    public function dailyNetForCurrency(string $code, int $days = 7): array
    {
        $code = strtoupper($code);
        $since = now()->subDays($days - 1)->startOfDay();

        $rows = CashMovement::query()
            ->forCurrency($code)
            ->where('created_at', '>=', $since)
            ->selectRaw('date(created_at) as d, sum(amount) as total')
            ->groupBy('d')
            ->pluck('total', 'd')
            ->all();

        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $out[$day] = (string) ($rows[$day] ?? '0');
        }

        return $out;
    }

    public function create(array $data): CashMovement
    {
        $data['currency_code'] = strtoupper((string) $data['currency_code']);
        if (isset($data['counter_currency_code']) && $data['counter_currency_code'] !== null) {
            $data['counter_currency_code'] = strtoupper((string) $data['counter_currency_code']);
        }
        $data['created_at'] = $data['created_at'] ?? now();

        $movement = new CashMovement;
        $movement->fill($data);
        $movement->save();

        return $movement;
    }

    public function countByType(CashMovementType $type, ?string $currency = null): int
    {
        $q = CashMovement::query()->ofType($type);
        if ($currency) {
            $q->forCurrency($currency);
        }

        return $q->count();
    }
}
