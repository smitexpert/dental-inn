<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface AccountRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator;

    public function findByCode(string $code): ?Account;

    public function findById(int $id): ?Account;

    public function findByIdOrFail(int $id): Account;

    /**
     * @return Collection<int, Account>
     */
    public function allActive(): Collection;

    public function create(array $data): Account;

    public function update(Account $account, array $data): Account;
}
