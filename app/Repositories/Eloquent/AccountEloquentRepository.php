<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Account;
use App\Repositories\Contracts\AccountRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountEloquentRepository implements AccountRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $q = Account::query();

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $q->where(function (Builder $w) use ($term): void {
                $w->where('code', 'like', $term)->orWhere('name', 'like', $term);
            });
        }

        if (! empty($filters['type'])) {
            $q->where('type', $filters['type']);
        }

        return $q->orderBy('code')->paginate($perPage)->withQueryString();
    }

    public function findByCode(string $code): ?Account
    {
        return Account::query()->where('code', $code)->first();
    }

    public function findById(int $id): ?Account
    {
        return Account::query()->find($id);
    }

    public function findByIdOrFail(int $id): Account
    {
        return Account::query()->findOrFail($id);
    }

    /**
     * @return Collection<int, Account>
     */
    public function allActive(): Collection
    {
        return Account::query()->active()->orderBy('code')->get();
    }

    public function create(array $data): Account
    {
        $account = new Account;
        $account->fill($data);
        $account->save();

        return $account;
    }

    public function update(Account $account, array $data): Account
    {
        $account->fill($data);
        $account->save();

        return $account;
    }
}
