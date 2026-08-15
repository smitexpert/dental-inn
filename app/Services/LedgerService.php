<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Account;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Single entry point for money movement in the application.
 *
 * Every public method writes one or more balanced LedgerEntry rows
 * (debit == credit). It is the only code path that writes to
 * `ledger_entries`; controllers and other services call into this
 * service rather than constructing entries themselves.
 */
class LedgerService
{
    /**
     * Record a single balanced entry.
     */
    public function recordEntry(
        Carbon $entryDate,
        string $description,
        int $debitAccountId,
        int $creditAccountId,
        string $amount,
        string $currencyCode = 'BDT',
        string $exchangeRate = '1.00000000',
        ?Model $reference = null,
        ?int $createdBy = null,
    ): LedgerEntry {
        if ((float) $amount <= 0) {
            throw new RuntimeException('Ledger entry amount must be positive.');
        }

        if ($debitAccountId === $creditAccountId) {
            throw new RuntimeException('Debit and credit accounts must differ.');
        }

        return DB::transaction(function () use (
            $entryDate,
            $description,
            $debitAccountId,
            $creditAccountId,
            $amount,
            $currencyCode,
            $exchangeRate,
            $reference,
            $createdBy,
        ): LedgerEntry {
            $entry = new LedgerEntry([
                'entry_date' => $entryDate->toDateString(),
                'description' => $description,
                'debit_account_id' => $debitAccountId,
                'credit_account_id' => $creditAccountId,
                'amount' => $amount,
                'currency_code' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'created_by' => $createdBy,
                'created_at' => now(),
            ]);

            if ($reference instanceof Model) {
                $entry->reference_type = $reference->getMorphClass();
                $entry->reference_id = (int) $reference->getKey();
            }

            $entry->save();

            return $entry;
        });
    }

    /**
     * Record invoice issue: Dr Accounts Receivable, Cr Service Revenue.
     * For product lines we could split COGS; we keep a single revenue
     * credit for simplicity (the invoice's items drive revenue detail
     * elsewhere).
     */
    public function recordInvoiceIssue(Invoice $invoice, ?int $userId = null): void
    {
        $ar = $this->accountOrFail('1100');
        $rev = $this->accountOrFail('4000');

        $this->recordEntry(
            Carbon::parse($invoice->invoice_date->toDateString()),
            sprintf('Invoice %s issued', $invoice->invoice_number),
            $ar->id,
            $rev->id,
            (string) $invoice->total,
            'BDT',
            '1.00000000',
            $invoice,
            $userId,
        );
    }

    /**
     * Record payment received. Cash method -> Dr Cash; everything else -> Dr Bank.
     * Credit side is always Accounts Receivable.
     */
    public function recordPaymentReceived(
        Payment $payment,
        string $cashAccountCode = '1000',
        string $bankAccountCode = '1010',
        ?int $userId = null,
    ): void {
        $cashAccount = $this->accountOrFail($cashAccountCode);
        $ar = $this->accountOrFail('1100');

        $debitId = $payment->method === PaymentMethod::Cash
            ? $cashAccount->id
            : $this->accountOrFail($bankAccountCode)->id;

        $this->recordEntry(
            Carbon::parse($payment->payment_date->toDateString()),
            sprintf('Payment for invoice %s', $payment->invoice?->invoice_number ?? (string) $payment->invoice_id),
            $debitId,
            $ar->id,
            (string) $payment->amount,
            $payment->currency_code,
            (string) $payment->exchange_rate,
            $payment,
            $userId,
        );
    }

    /**
     * Record approved expense: Dr expense category account, Cr Cash/Bank.
     */
    public function recordExpenseApproved(Expense $expense, ?int $userId = null): void
    {
        $categoryAccount = Account::query()->findOrFail((int) $expense->category->account_id);
        $cashAccount = $this->accountOrFail('1000');

        $creditId = $expense->payment_method === PaymentMethod::Cash
            ? $cashAccount->id
            : $this->accountOrFail('1010')->id;

        $this->recordEntry(
            Carbon::parse($expense->expense_date->toDateString()),
            sprintf('Expense approved: %s', $expense->vendor ?? $expense->description ?? '—'),
            $categoryAccount->id,
            $creditId,
            (string) $expense->amount,
            $expense->currency_code,
            (string) $expense->exchange_rate,
            $expense,
            $userId,
        );
    }

    protected function accountOrFail(string $code): Account
    {
        $account = Account::query()->where('code', $code)->first();
        if (! $account) {
            throw new RuntimeException("Account {$code} not found in chart of accounts.");
        }

        return $account;
    }
}
