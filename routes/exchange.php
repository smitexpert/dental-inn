<?php

declare(strict_types=1);

use App\Http\Controllers\Exchange\CashMovementController;
use App\Http\Controllers\Exchange\DashboardController as ExchangeDashboardController;
use App\Http\Controllers\Exchange\ExchangeRateController;
use App\Http\Controllers\Exchange\ReconciliationController;
use Illuminate\Support\Facades\Route;

// Dashboard
Route::middleware(['auth', 'active', 'permission:view-exchange-rates'])
    ->get('/exchange', [ExchangeDashboardController::class, 'index'])
    ->name('exchange.dashboard');

// Exchange rates
Route::middleware(['auth', 'active', 'permission:view-exchange-rates'])
    ->prefix('exchange/rates')->name('exchange.rates.')->group(function (): void {
        Route::get('/', [ExchangeRateController::class, 'index'])->name('index');
    });

Route::middleware(['auth', 'active', 'permission:manage-exchange-rates'])
    ->prefix('exchange/rates')->name('exchange.rates.')->group(function (): void {
        Route::post('/', [ExchangeRateController::class, 'store'])->name('store');
        Route::patch('/{rate}', [ExchangeRateController::class, 'update'])->name('update');
        Route::delete('/{rate}', [ExchangeRateController::class, 'destroy'])->name('destroy');
    });

// Cash movements
// 1) record-cash-movements MUST be declared before the view routes that
//    bind a model on `/{movement}` so that the literal "create" path
//    isn't resolved as a model id (which would 404 the user).
Route::middleware(['auth', 'active', 'permission:record-cash-movements'])
    ->prefix('exchange/movements')->name('exchange.movements.')->group(function (): void {
        Route::get('/create', [CashMovementController::class, 'create'])->name('create');
        Route::post('/', [CashMovementController::class, 'store'])->name('store');
    });

Route::middleware(['auth', 'active', 'permission:view-cash-movements'])
    ->prefix('exchange/movements')->name('exchange.movements.')->group(function (): void {
        Route::get('/', [CashMovementController::class, 'index'])->name('index');
        Route::get('/{movement}', [CashMovementController::class, 'show'])->name('show');
    });

// Reconciliation
Route::middleware(['auth', 'active', 'permission:reconcile-cash-drawer'])
    ->prefix('exchange/reconcile')->name('exchange.reconcile.')->group(function (): void {
        Route::get('/', [ReconciliationController::class, 'index'])->name('index');
        Route::post('/run', [ReconciliationController::class, 'run'])->name('run');
    });
