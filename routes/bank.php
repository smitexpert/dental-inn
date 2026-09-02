<?php

declare(strict_types=1);

use App\Http\Controllers\Bank\AccountController;
use App\Http\Controllers\Bank\DashboardController;
use App\Http\Controllers\Bank\ReconciliationController;
use App\Http\Controllers\Bank\TransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'permission:view-accounts'])
    ->get('/bank', [DashboardController::class, 'index'])
    ->name('bank.dashboard');

Route::middleware(['auth', 'active', 'permission:view-accounts'])
    ->prefix('bank/accounts')->name('bank.accounts.')->group(function (): void {
        Route::get('/', [AccountController::class, 'index'])->name('index');
    });

Route::middleware(['auth', 'active', 'permission:manage-accounts'])
    ->prefix('bank/accounts')->name('bank.accounts.')->group(function (): void {
        Route::get('/create', [AccountController::class, 'create'])->name('create');
        Route::post('/', [AccountController::class, 'store'])->name('store');
        Route::get('/{account}/edit', [AccountController::class, 'edit'])->name('edit');
        Route::patch('/{account}', [AccountController::class, 'update'])->name('update');
    });

Route::middleware(['auth', 'active', 'permission:record-transfers'])
    ->prefix('bank/transfers')->name('bank.transfers.')->group(function (): void {
        Route::get('/create', [TransferController::class, 'create'])->name('create');
        Route::post('/', [TransferController::class, 'store'])->name('store');
    });

Route::middleware(['auth', 'active', 'permission:view-transfers'])
    ->prefix('bank/transfers')->name('bank.transfers.')->group(function (): void {
        Route::get('/', [TransferController::class, 'index'])->name('index');
        Route::get('/{transfer}', [TransferController::class, 'show'])->name('show');
    });

Route::middleware(['auth', 'active', 'permission:reconcile-accounts'])
    ->prefix('bank/reconcile')->name('bank.reconcile.')->group(function (): void {
        Route::get('/', [ReconciliationController::class, 'index'])->name('index');
        Route::post('/run', [ReconciliationController::class, 'run'])->name('run');
    });
