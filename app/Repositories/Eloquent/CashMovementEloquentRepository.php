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
        $q = CashMovement::query()->with(['account', 'counterAccount', 'performer']);

        if (! empty($filters['account_id'])) {
            $q->where('account_id', (int) $filters['account_id']);
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

    public function sumForAccountSince(int $accountId, Carbon $since): string
    {
        $sum = CashMovement::query()
            ->forAccount($accountId)
            ->where('created_at', '>=', $since)
            ->sum('amount');

        return (string) $sum;
    }

    public function create(array $data): CashMovement
    {
        $data['created_at'] = $data['created_at'] ?? now();

        $movement = new CashMovement;
        $movement->fill($data);
        $movement->save();

        return $movement;
    }

    public function countByType(CashMovementType $type, ?int $accountId = null): int
    {
        $q = CashMovement::query()->ofType($type);
        if ($accountId) {
            $q->forAccount($accountId);
        }

        return $q->count();
    }
}
