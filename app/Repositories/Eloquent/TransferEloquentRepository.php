<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Transfer;
use App\Repositories\Contracts\TransferRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class TransferEloquentRepository implements TransferRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $q = Transfer::query()->with(['fromAccount', 'toAccount', 'performer']);

        if (! empty($filters['date_from'])) {
            $q->whereDate('occurred_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $q->whereDate('occurred_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['from_account_id'])) {
            $q->where('from_account_id', (int) $filters['from_account_id']);
        }

        if (! empty($filters['to_account_id'])) {
            $q->where('to_account_id', (int) $filters['to_account_id']);
        }

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $q->where(function (Builder $w) use ($term): void {
                $w->where('reference', 'like', $term)->orWhere('note', 'like', $term);
            });
        }

        return $q->orderByDesc('occurred_at')->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function findByIdOrFail(int $id): Transfer
    {
        return Transfer::query()->with(['fromAccount', 'toAccount', 'performer', 'movements.account'])
            ->findOrFail($id);
    }

    public function create(array $data): Transfer
    {
        $transfer = new Transfer;
        $transfer->fill($data);
        $transfer->save();

        return $transfer;
    }
}
