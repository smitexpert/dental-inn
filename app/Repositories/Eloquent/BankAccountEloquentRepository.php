<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\BankAccount;
use App\Repositories\Contracts\BankAccountRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BankAccountEloquentRepository implements BankAccountRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $q = BankAccount::query();

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $q->where(function (Builder $w) use ($term): void {
                $w->where('name', 'like', $term)
                    ->orWhere('bank_name', 'like', $term)
                    ->orWhere('account_number', 'like', $term);
            });
        }

        if (! empty($filters['type'])) {
            $q->where('type', $filters['type']);
        }

        return $q->orderBy('name')->paginate($perPage)->withQueryString();
    }

    public function allActive(): Collection
    {
        return BankAccount::query()->active()->orderBy('name')->get();
    }

    public function findById(int $id): ?BankAccount
    {
        return BankAccount::query()->find($id);
    }

    public function findByIdOrFail(int $id): BankAccount
    {
        return BankAccount::query()->findOrFail($id);
    }

    public function findSystemByName(string $name): ?BankAccount
    {
        return BankAccount::query()->where('name', $name)->where('is_system', true)->first();
    }

    public function lockById(int $id): BankAccount
    {
        $row = BankAccount::query()->where('id', $id)->lockForUpdate()->first();
        if (! $row) {
            throw new RuntimeException("Bank account {$id} not found.");
        }

        return $row;
    }

    public function incrementBalance(int $id, string $delta): void
    {
        DB::table('bank_accounts')
            ->where('id', $id)
            ->update([
                'balance' => DB::raw('balance + '.((float) $delta)),
                'updated_at' => now(),
            ]);
    }

    public function stampReconciliation(int $id, string $countedBalance): void
    {
        DB::table('bank_accounts')
            ->where('id', $id)
            ->update([
                'last_reconciled_at' => now(),
                'last_reconciled_balance' => $countedBalance,
                'updated_at' => now(),
            ]);
    }

    public function create(array $data): BankAccount
    {
        $account = new BankAccount;
        $account->fill($data);
        $account->save();

        return $account;
    }

    public function update(BankAccount $account, array $data): BankAccount
    {
        $account->fill($data);
        $account->save();

        return $account;
    }
}
