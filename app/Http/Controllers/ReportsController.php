<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\InvoiceStatus;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    public function ledger(Request $request): Response
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $q = LedgerEntry::query()->with(['debitAccount', 'creditAccount', 'reference']);

        if ($dateFrom) {
            $q->whereDate('entry_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $q->whereDate('entry_date', '<=', $dateTo);
        }

        $entries = $q->orderByDesc('entry_date')->orderByDesc('id')->paginate(50);

        return Inertia::render('Reports/Ledger', [
            'entries' => $entries,
            'filters' => $request->only(['date_from', 'date_to']),
            'totals' => [
                'debits' => (float) LedgerEntry::query()->when($dateFrom, fn ($q2) => $q2->whereDate('entry_date', '>=', $dateFrom))
                    ->when($dateTo, fn ($q2) => $q2->whereDate('entry_date', '<=', $dateTo))
                    ->sum('amount'),
            ],
        ]);
    }

    public function incomeStatement(Request $request): Response
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $revenue = (float) Account::query()->where('type', AccountType::Revenue->value)->get()
            ->sum(fn (Account $a) => $this->balanceForAccount($a, $dateFrom, $dateTo));

        $expense = (float) Account::query()->where('type', AccountType::Expense->value)->get()
            ->sum(fn (Account $a) => $this->balanceForAccount($a, $dateFrom, $dateTo));

        $net = $revenue - $expense;

        return Inertia::render('Reports/IncomeStatement', [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'revenue' => $revenue,
            'expense' => $expense,
            'net' => $net,
            'revenue_accounts' => Account::query()->where('type', AccountType::Revenue->value)->orderBy('code')
                ->get()->map(fn (Account $a) => [
                    'id' => $a->id,
                    'code' => $a->code,
                    'name' => $a->name,
                    'balance' => $this->balanceForAccount($a, $dateFrom, $dateTo),
                ])->all(),
            'expense_accounts' => Account::query()->where('type', AccountType::Expense->value)->orderBy('code')
                ->get()->map(fn (Account $a) => [
                    'id' => $a->id,
                    'code' => $a->code,
                    'name' => $a->name,
                    'balance' => $this->balanceForAccount($a, $dateFrom, $dateTo),
                ])->all(),
        ]);
    }

    public function balanceSheet(Request $request): Response
    {
        $asOf = $request->input('as_of', now()->toDateString());

        $assetAccounts = Account::query()->where('type', AccountType::Asset->value)->orderBy('code')
            ->get()->map(fn (Account $a) => [
                'id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
                'balance' => $this->balanceForAccount($a, null, $asOf),
            ])->all();

        $liabilityAccounts = Account::query()->where('type', AccountType::Liability->value)->orderBy('code')
            ->get()->map(fn (Account $a) => [
                'id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
                'balance' => $this->balanceForAccount($a, null, $asOf),
            ])->all();

        $equityAccounts = Account::query()->where('type', AccountType::Equity->value)->orderBy('code')
            ->get()->map(fn (Account $a) => [
                'id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
                'balance' => $this->balanceForAccount($a, null, $asOf),
            ])->all();

        $totalAssets = array_sum(array_column($assetAccounts, 'balance'));
        $totalLiabilities = array_sum(array_column($liabilityAccounts, 'balance'));
        $totalEquity = array_sum(array_column($equityAccounts, 'balance'));

        return Inertia::render('Reports/BalanceSheet', [
            'as_of' => $asOf,
            'assets' => $assetAccounts,
            'liabilities' => $liabilityAccounts,
            'equity' => $equityAccounts,
            'totals' => [
                'assets' => $totalAssets,
                'liabilities' => $totalLiabilities,
                'equity' => $totalEquity,
            ],
        ]);
    }

    public function accountsReceivable(Request $request): Response
    {
        $asOf = $request->input('as_of', now()->toDateString());

        $open = Invoice::query()
            ->with('patient')
            ->whereIn('status', [InvoiceStatus::Issued->value, InvoiceStatus::PartiallyPaid->value, InvoiceStatus::Overdue->value])
            ->where('due_date', '<=', $asOf)
            ->get();

        $buckets = [
            '0-30' => 0.0,
            '31-60' => 0.0,
            '61-90' => 0.0,
            '90+' => 0.0,
        ];

        $rows = [];
        foreach ($open as $inv) {
            $balance = (float) $inv->total - (float) $inv->paid_amount;
            $days = (int) \Carbon\Carbon::parse($inv->due_date)->diffInDays($asOf, false) * -1;
            $days = max(0, $days);
            if ($days <= 30) {
                $buckets['0-30'] += $balance;
            } elseif ($days <= 60) {
                $buckets['31-60'] += $balance;
            } elseif ($days <= 90) {
                $buckets['61-90'] += $balance;
            } else {
                $buckets['90+'] += $balance;
            }

            $rows[] = [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'patient_name' => $inv->patient?->name,
                'patient_code' => $inv->patient?->patient_code,
                'invoice_date' => $inv->invoice_date->toDateString(),
                'due_date' => $inv->due_date->toDateString(),
                'total' => (string) $inv->total,
                'paid_amount' => (string) $inv->paid_amount,
                'balance' => (string) number_format($balance, 2, '.', ''),
                'days_past_due' => $days,
                'status' => $inv->status->value,
            ];
        }

        return Inertia::render('Reports/AccountsReceivable', [
            'as_of' => $asOf,
            'rows' => $rows,
            'buckets' => $buckets,
            'total_outstanding' => array_sum($buckets),
        ]);
    }

    protected function balanceForAccount(Account $account, ?string $dateFrom, ?string $dateTo): float
    {
        $debit = (float) LedgerEntry::query()->where('debit_account_id', $account->id)
            ->when($dateFrom, fn ($q) => $q->whereDate('entry_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('entry_date', '<=', $dateTo))
            ->sum('amount');

        $credit = (float) LedgerEntry::query()->where('credit_account_id', $account->id)
            ->when($dateFrom, fn ($q) => $q->whereDate('entry_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('entry_date', '<=', $dateTo))
            ->sum('amount');

        return match ($account->type) {
            AccountType::Asset, AccountType::Expense => $debit - $credit,
            AccountType::Liability, AccountType::Equity, AccountType::Revenue => $credit - $debit,
        };
    }
}
