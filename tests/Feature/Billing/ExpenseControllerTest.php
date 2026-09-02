<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\LedgerEntry;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);
        $this->seed(ExpenseCategorySeeder::class);
    }

    public function test_receptionist_can_submit_expense(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('receptionist');
        $category = ExpenseCategory::query()->where('name', 'Utilities')->firstOrFail();

        $response = $this->actingAs($user)->post('/billing/expenses', [
            'expense_date' => '2026-08-14',
            'category_id' => $category->id,
            'amount' => 250.50,
            'currency_code' => 'BDT',
            'exchange_rate' => 1.0,
            'payment_method' => PaymentMethod::Cash->value,
            'vendor' => 'Power Co',
            'description' => 'Electricity',
        ]);

        $response->assertRedirect();
        $expense = Expense::query()->first();
        $this->assertSame(ExpenseStatus::Pending->value, $expense->status->value);
    }

    public function test_admin_can_approve_expense(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $category = ExpenseCategory::query()->where('name', 'Rent')->firstOrFail();
        $expense = Expense::create([
            'expense_date' => '2026-08-14',
            'category_id' => $category->id,
            'amount' => 1000,
            'currency_code' => 'BDT',
            'exchange_rate' => 1.0,
            'payment_method' => PaymentMethod::BankTransfer->value,
            'vendor' => 'Landlord',
            'status' => ExpenseStatus::Pending->value,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post("/billing/expenses/{$expense->id}/approve");
        $response->assertRedirect();

        $expense->refresh();
        $this->assertSame(ExpenseStatus::Approved->value, $expense->status->value);
        $this->assertSame(1, LedgerEntry::query()->where('reference_type', 'expense')->count());
    }

    public function test_receptionist_cannot_approve_expense(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('receptionist');
        $category = ExpenseCategory::query()->where('name', 'Other')->firstOrFail();
        $expense = Expense::create([
            'expense_date' => '2026-08-14',
            'category_id' => $category->id,
            'amount' => 100,
            'currency_code' => 'BDT',
            'exchange_rate' => 1.0,
            'payment_method' => PaymentMethod::Cash->value,
            'status' => ExpenseStatus::Pending->value,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post("/billing/expenses/{$expense->id}/approve");
        $response->assertForbidden();
    }

    public function test_admin_can_reject_expense(): void
    {
        $user = User::factory()->create(['is_active' => true])->assignRole('admin');
        $category = ExpenseCategory::query()->where('name', 'Salaries')->firstOrFail();
        $expense = Expense::create([
            'expense_date' => '2026-08-14',
            'category_id' => $category->id,
            'amount' => 500,
            'currency_code' => 'BDT',
            'exchange_rate' => 1.0,
            'payment_method' => PaymentMethod::Cash->value,
            'status' => ExpenseStatus::Pending->value,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post("/billing/expenses/{$expense->id}/reject");
        $response->assertRedirect();

        $expense->refresh();
        $this->assertSame(ExpenseStatus::Rejected->value, $expense->status->value);
        $this->assertSame(0, LedgerEntry::query()->where('reference_type', 'expense')->count());
    }
}
