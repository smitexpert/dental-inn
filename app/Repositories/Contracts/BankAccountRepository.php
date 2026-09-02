<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface BankAccountRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator;

    /** @return Collection<int, BankAccount> */
    public function allActive(): Collection;

    public function findById(int $id): ?BankAccount;

    public function findByIdOrFail(int $id): BankAccount;

    public function findSystemByName(string $name): ?BankAccount;

    /**
     * Lock the row for update. MUST be called inside a DB transaction.
     */
    public function lockById(int $id): BankAccount;

    public function incrementBalance(int $id, string $delta): void;

    public function stampReconciliation(int $id, string $countedBalance): void;

    public function create(array $data): BankAccount;

    public function update(BankAccount $account, array $data): BankAccount;
}
