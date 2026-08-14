<?php

declare(strict_types=1);

use App\Http\Controllers\Billing\AccountController;
use App\Http\Controllers\Billing\ExpenseController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\PaymentController;
use Illuminate\Support\Facades\Route;

// Chart of accounts
Route::middleware(['auth', 'active', 'permission:view-invoices'])
    ->prefix('billing/accounts')->name('billing.accounts.')->group(function (): void {
        Route::get('/', [AccountController::class, 'index'])->name('index');
        Route::get('/{account}', [AccountController::class, 'show'])->name('show');
    });

Route::middleware(['auth', 'active', 'permission:manage-chart-of-accounts'])
    ->prefix('billing/accounts')->name('billing.accounts.')->group(function (): void {
        Route::get('/create', [AccountController::class, 'create'])->name('create');
        Route::post('/', [AccountController::class, 'store'])->name('store');
        Route::get('/{account}/edit', [AccountController::class, 'edit'])->name('edit');
        Route::patch('/{account}', [AccountController::class, 'update'])->name('update');
    });

// Invoices
Route::middleware(['auth', 'active', 'permission:view-invoices'])
    ->prefix('billing/invoices')->name('billing.invoices.')->group(function (): void {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
    });

Route::middleware(['auth', 'active', 'permission:manage-invoices'])
    ->prefix('billing/invoices')->name('billing.invoices.')->group(function (): void {
        Route::get('/create', [InvoiceController::class, 'create'])->name('create');
        Route::post('/', [InvoiceController::class, 'store'])->name('store');
        Route::get('/{invoice}/edit', [InvoiceController::class, 'edit'])->name('edit');
        Route::patch('/{invoice}', [InvoiceController::class, 'update'])->name('update');
        Route::post('/{invoice}/issue', [InvoiceController::class, 'issue'])->name('issue');
        Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('cancel');
    });

// Payments
Route::middleware(['auth', 'active', 'permission:view-payments'])
    ->prefix('billing/payments')->name('billing.payments.')->group(function (): void {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('/{payment}/receipt', [PaymentController::class, 'receipt'])->name('receipt');
    });

Route::middleware(['auth', 'active', 'permission:manage-payments'])
    ->prefix('billing/payments')->name('billing.payments.')->group(function (): void {
        Route::post('/', [PaymentController::class, 'store'])->name('store');
    });

// Expenses
Route::middleware(['auth', 'active', 'permission:view-expenses'])
    ->prefix('billing/expenses')->name('billing.expenses.')->group(function (): void {
        Route::get('/', [ExpenseController::class, 'index'])->name('index');
        Route::get('/{expense}', [ExpenseController::class, 'show'])->name('show');
    });

Route::middleware(['auth', 'active', 'permission:manage-expenses'])
    ->prefix('billing/expenses')->name('billing.expenses.')->group(function (): void {
        Route::get('/create', [ExpenseController::class, 'create'])->name('create');
        Route::post('/', [ExpenseController::class, 'store'])->name('store');
        Route::get('/{expense}/edit', [ExpenseController::class, 'edit'])->name('edit');
        Route::patch('/{expense}', [ExpenseController::class, 'update'])->name('update');
        Route::post('/{expense}/approve', [ExpenseController::class, 'approve'])->name('approve');
        Route::post('/{expense}/reject', [ExpenseController::class, 'reject'])->name('reject');
    });
