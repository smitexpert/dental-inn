<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Expense;
use App\Repositories\Contracts\ExpenseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ExpenseEloquentRepository implements ExpenseRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $q = Expense::query()->with(['category', 'creator', 'approver']);

        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        if (! empty($filters['category_id'])) {
            $q->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['date_from'])) {
            $q->whereDate('expense_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $q->whereDate('expense_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $q->where(function (Builder $w) use ($term): void {
                $w->where('vendor', 'like', $term)->orWhere('description', 'like', $term);
            });
        }

        return $q->orderByDesc('expense_date')->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function findById(int $id): ?Expense
    {
        return Expense::query()->find($id);
    }

    public function findByIdOrFail(int $id): Expense
    {
        return Expense::query()->findOrFail($id);
    }

    public function create(array $data): Expense
    {
        $expense = new Expense;
        $expense->fill($data);
        $expense->save();

        return $expense;
    }

    public function update(Expense $expense, array $data): Expense
    {
        $expense->fill($data);
        $expense->save();

        return $expense;
    }
}
