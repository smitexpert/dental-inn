<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\ExpenseCategory;
use Illuminate\Console\Command;

/**
 * Backfill helper: for every active expense-type Account that does not
 * already have an ExpenseCategory, create one. Run this once after
 * upgrading so existing chart-of-accounts rows show up in the Submit
 * Expense dropdown without needing a manual step.
 */
class SyncExpenseCategories extends Command
{
    protected $signature = 'expenses:sync-categories {--dry-run : Show what would be created without writing}';

    protected $description = 'Create ExpenseCategory rows for every active expense-type Account that is missing one';

    public function handle(): int
    {
        $accounts = Account::query()
            ->where('type', AccountType::Expense->value)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($accounts as $account) {
            $exists = ExpenseCategory::query()
                ->where('account_id', $account->id)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $this->line(sprintf(
                '%s %s — %s',
                $this->option('dry-run') ? '[dry-run] would create' : 'Creating',
                $account->code,
                $account->name,
            ));

            if (! $this->option('dry-run')) {
                ExpenseCategory::query()->firstOrCreate(
                    ['name' => $account->name],
                    [
                        'account_id' => $account->id,
                        'is_active' => true,
                    ],
                );
            }
            $created++;
        }

        $this->info(sprintf('Done. %d created, %d skipped.', $created, $skipped));

        return self::SUCCESS;
    }
}
