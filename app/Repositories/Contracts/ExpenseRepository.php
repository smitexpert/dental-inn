<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Expense;
use Illuminate\Pagination\LengthAwarePaginator;

interface ExpenseRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?Expense;

    public function findByIdOrFail(int $id): Expense;

    public function create(array $data): Expense;

    public function update(Expense $expense, array $data): Expense;
}
