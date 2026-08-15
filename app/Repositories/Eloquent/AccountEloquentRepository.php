<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\ExpenseCategory;
use App\Repositories\Contracts\AccountRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
        // Wrap in a transaction so the Account + the auto-created
        // ExpenseCategory commit (or roll back) together.
        return DB::transaction(function () use ($data): Account {
            $account = new Account;
            $account->fill($data);
            $account->save();

            // When the user adds a new expense-type account to the chart
            // of accounts, mirror it as an active ExpenseCategory so it
            // surfaces in the Submit Expense form's category dropdown
            // without requiring a second manual step.
            $type = $account->type;
            if ($type === AccountType::Expense) {
                ExpenseCategory::query()->firstOrCreate(
                    ['name' => $account->name],
                    [
                        'account_id' => $account->id,
                        'is_active' => (bool) ($data['is_active'] ?? true),
                    ],
                );
            }

            return $account;
        });
    }

    public function update(Account $account, array $data): Account
    {
        $account->fill($data);
        $account->save();

        // Keep the mirrored ExpenseCategory in sync if the account name
        // or active flag changed. We don't auto-create on type change
        // because the user might be reclassifying an existing account.
        if ($account->type === AccountType::Expense) {
            ExpenseCategory::query()
                ->where('account_id', $account->id)
                ->update(['is_active' => (bool) ($account->is_active ?? true)]);
        }

        return $account;
    }
}
