<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Console\Command;

class MarkInvoicesOverdue extends Command
{
    protected $signature = 'invoices:mark-overdue {--today= : ISO date to evaluate against}';

    protected $description = 'Flip issued/partially_paid invoices past due_date to overdue (idempotent).';

    public function handle(): int
    {
        $today = (string) ($this->option('today') ?: now()->toDateString());

        $count = Invoice::query()
            ->whereIn('status', [InvoiceStatus::Issued->value, InvoiceStatus::PartiallyPaid->value])
            ->whereDate('due_date', '<', $today)
            ->update(['status' => InvoiceStatus::Overdue->value]);

        $this->info("Marked {$count} invoice(s) as overdue (as of {$today}).");

        return self::SUCCESS;
    }
}
