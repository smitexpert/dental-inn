<?php

declare(strict_types=1);

use App\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'permission:view-reports'])
    ->prefix('reports')->name('reports.')->group(function (): void {
        Route::get('/ledger', [ReportsController::class, 'ledger'])->name('ledger');
        Route::get('/income-statement', [ReportsController::class, 'incomeStatement'])->name('income-statement');
        Route::get('/balance-sheet', [ReportsController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('/accounts-receivable', [ReportsController::class, 'accountsReceivable'])->name('accounts-receivable');
    });
