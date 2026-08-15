<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ExpenseCategory;
use App\Repositories\Contracts\ExpenseCategoryRepository;
use Illuminate\Database\Eloquent\Collection;

class ExpenseCategoryEloquentRepository implements ExpenseCategoryRepository
{
    public function findById(int $id): ?ExpenseCategory
    {
        return ExpenseCategory::query()->find($id);
    }

    public function findByIdOrFail(int $id): ExpenseCategory
    {
        return ExpenseCategory::query()->findOrFail($id);
    }

    public function findByName(string $name): ?ExpenseCategory
    {
        return ExpenseCategory::query()->where('name', $name)->first();
    }

    /**
     * @return Collection<int, ExpenseCategory>
     */
    public function allActive(): Collection
    {
        return ExpenseCategory::query()->active()->orderBy('name')->get();
    }

    public function create(array $data): ExpenseCategory
    {
        $category = new ExpenseCategory;
        $category->fill($data);
        $category->save();

        return $category;
    }

    public function update(ExpenseCategory $category, array $data): ExpenseCategory
    {
        $category->fill($data);
        $category->save();

        return $category;
    }
}
