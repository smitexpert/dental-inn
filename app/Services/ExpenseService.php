<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Approves expenses and posts the matching ledger entry.
 */
class ExpenseService
{
    public function __construct(private readonly LedgerService $ledger) {}

    public function approve(Expense $expense, int $userId): Expense
    {
        if ($expense->status !== ExpenseStatus::Pending) {
            throw new RuntimeException('Only pending expenses can be approved.');
        }

        return DB::transaction(function () use ($expense, $userId): Expense {
            $expense->status = ExpenseStatus::Approved;
            $expense->approved_by = $userId;
            $expense->approved_at = now();
            $expense->save();

            $this->ledger->recordExpenseApproved($expense->fresh(), $userId);

            return $expense->fresh();
        });
    }

    public function reject(Expense $expense, int $userId): Expense
    {
        if ($expense->status !== ExpenseStatus::Pending) {
            throw new RuntimeException('Only pending expenses can be rejected.');
        }

        $expense->status = ExpenseStatus::Rejected;
        $expense->approved_by = $userId;
        $expense->approved_at = now();
        $expense->save();

        return $expense->fresh();
    }
}
