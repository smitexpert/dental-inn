<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Collection;

interface ExpenseCategoryRepository
{
    public function findById(int $id): ?ExpenseCategory;

    public function findByIdOrFail(int $id): ExpenseCategory;

    public function findByName(string $name): ?ExpenseCategory;

    /**
     * @return Collection<int, ExpenseCategory>
     */
    public function allActive(): Collection;

    public function create(array $data): ExpenseCategory;

    public function update(ExpenseCategory $category, array $data): ExpenseCategory;
}
