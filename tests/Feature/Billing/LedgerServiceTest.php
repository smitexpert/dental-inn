<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\ExpenseStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Account;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\User;
use App\Services\ExpenseService;
use App\Services\LedgerService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\ExpenseCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountsSeeder::class);
        $this->seed(ExpenseCategorySeeder::class);
    }

    public function test_record_entry_creates_balanced_ledger_row(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $cash = Account::query()->where('code', '1000')->firstOrFail();
        $ar = Account::query()->where('code', '1100')->firstOrFail();

        $entry = app(LedgerService::class)->recordEntry(
            Carbon::parse('2026-08-14'),
            'Test entry',
            $cash->id,
            $ar->id,
            '125.5000',
            'BDT',
            '1.00000000',
            null,
            $user->id,
        );

        $this->assertNotNull($entry->id);
        $this->assertSame('125.5000', (string) $entry->amount);
        $this->assertSame($cash->id, $entry->debit_account_id);
        $this->assertSame($ar->id, $entry->credit_account_id);
        $this->assertSame(1, LedgerEntry::count());
    }

    public function test_record_entry_rejects_zero_amount(): void
    {
        $cash = Account::query()->where('code', '1000')->firstOrFail();
        $ar = Account::query()->where('code', '1100')->firstOrFail();

        $this->expectException(RuntimeException::class);
        app(LedgerService::class)->recordEntry(
            Carbon::parse('2026-08-14'),
            'Zero',
            $cash->id,
            $ar->id,
            '0.00',
            'BDT',
            '1.0',
            null,
            null,
        );
    }

    public function test_invoice_issue_creates_ledger_entry(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = Invoice::factory()->create([
            'invoice_number' => 'INV-9001',
            'invoice_date' => '2026-08-14',
            'status' => InvoiceStatus::Issued->value,
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total' => 100,
        ]);

        app(LedgerService::class)->recordInvoiceIssue($invoice, $user->id);

        $entry = LedgerEntry::query()->where('reference_type', 'invoice')->first();
        $this->assertNotNull($entry);
        $this->assertSame('100.0000', (string) $entry->amount);

        $ar = Account::query()->where('code', '1100')->firstOrFail();
        $rev = Account::query()->where('code', '4000')->firstOrFail();
        $this->assertSame($ar->id, $entry->debit_account_id);
        $this->assertSame($rev->id, $entry->credit_account_id);
    }

    public function test_payment_received_creates_ledger_entry(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = Invoice::factory()->create([
            'invoice_number' => 'INV-9002',
            'total' => 500,
            'status' => InvoiceStatus::Issued->value,
        ]);
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'payment_date' => '2026-08-14',
            'amount' => 250,
            'currency_code' => 'BDT',
            'exchange_rate' => 1.0,
            'method' => PaymentMethod::Cash->value,
            'received_by' => $user->id,
            'created_at' => now(),
        ]);

        app(LedgerService::class)->recordPaymentReceived($payment, '1000', '1010', $user->id);

        $entry = LedgerEntry::query()->where('reference_type', 'payment')->first();
        $this->assertNotNull($entry);
        $cash = Account::query()->where('code', '1000')->firstOrFail();
        $ar = Account::query()->where('code', '1100')->firstOrFail();
        $this->assertSame($cash->id, $entry->debit_account_id);
        $this->assertSame($ar->id, $entry->credit_account_id);
    }

    public function test_expense_approval_creates_ledger_entry(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $category = ExpenseCategory::query()->where('name', 'Rent')->firstOrFail();
        $expense = Expense::create([
            'expense_date' => '2026-08-14',
            'category_id' => $category->id,
            'amount' => 1200,
            'currency_code' => 'BDT',
            'exchange_rate' => 1.0,
            'payment_method' => PaymentMethod::BankTransfer->value,
            'vendor' => 'Landlord',
            'description' => 'August rent',
            'status' => ExpenseStatus::Pending->value,
            'created_by' => $user->id,
        ]);

        $expense = app(ExpenseService::class)->approve($expense, $user->id);

        $entry = LedgerEntry::query()->where('reference_type', 'expense')->first();
        $this->assertNotNull($entry);
        $rent = Account::query()->where('code', '6000')->firstOrFail();
        $bank = Account::query()->where('code', '1010')->firstOrFail();
        $this->assertSame($rent->id, $entry->debit_account_id);
        $this->assertSame($bank->id, $entry->credit_account_id);
        $this->assertSame(ExpenseStatus::Approved->value, $expense->status->value);
    }
}
