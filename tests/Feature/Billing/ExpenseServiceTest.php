<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\ExpenseService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\ExpenseCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ExpenseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountsSeeder::class);
        $this->seed(ExpenseCategorySeeder::class);
    }

    public function test_approve_marks_approved_and_posts_ledger(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $category = ExpenseCategory::query()->where('name', 'Utilities')->firstOrFail();
        $expense = Expense::create([
            'expense_date' => '2026-08-14',
            'category_id' => $category->id,
            'amount' => 250,
            'currency_code' => 'BDT',
            'exchange_rate' => 1.0,
            'payment_method' => PaymentMethod::Cash->value,
            'vendor' => 'Power Co',
            'status' => ExpenseStatus::Pending->value,
            'created_by' => $user->id,
        ]);

        $approved = app(ExpenseService::class)->approve($expense, $user->id);

        $this->assertSame(ExpenseStatus::Approved->value, $approved->status->value);
        $this->assertSame($user->id, $approved->approved_by);
        $this->assertNotNull($approved->approved_at);
        $this->assertSame(1, LedgerEntry::query()->where('reference_type', 'expense')->count());
    }

    public function test_reject_marks_rejected_without_ledger_entry(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $category = ExpenseCategory::query()->where('name', 'Supplies')->firstOrFail();
        $expense = Expense::create([
            'expense_date' => '2026-08-14',
            'category_id' => $category->id,
            'amount' => 100,
            'currency_code' => 'BDT',
            'exchange_rate' => 1.0,
            'payment_method' => PaymentMethod::Cash->value,
            'vendor' => 'Supplier',
            'status' => ExpenseStatus::Pending->value,
            'created_by' => $user->id,
        ]);

        $rejected = app(ExpenseService::class)->reject($expense, $user->id);

        $this->assertSame(ExpenseStatus::Rejected->value, $rejected->status->value);
        $this->assertSame(0, LedgerEntry::query()->where('reference_type', 'expense')->count());
    }

    public function test_approve_already_approved_throws(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $category = ExpenseCategory::query()->where('name', 'Rent')->firstOrFail();
        $expense = Expense::create([
            'expense_date' => '2026-08-14',
            'category_id' => $category->id,
            'amount' => 1000,
            'currency_code' => 'BDT',
            'exchange_rate' => 1.0,
            'payment_method' => PaymentMethod::Cash->value,
            'status' => ExpenseStatus::Approved->value,
            'created_by' => $user->id,
        ]);

        $this->expectException(RuntimeException::class);
        app(ExpenseService::class)->approve($expense, $user->id);
    }
}
