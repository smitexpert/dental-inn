<?php

declare(strict_types=1);

use App\Http\Controllers\Inventory\LowStockController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\StockLocationController;
use App\Http\Controllers\Inventory\StockOperationController;
use App\Http\Controllers\Inventory\SupplierController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'permission:view-products'])
    ->prefix('inventory')->name('inventory.')->group(function (): void {
        Route::get('/products/api/search', [ProductController::class, 'search'])->name('products.search');
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/low-stock', [LowStockController::class, 'index'])->name('low-stock');
        Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::get('/products/{product}/movements', [ProductController::class, 'movements'])->name('products.movements');

        Route::middleware('permission:view-suppliers')
            ->prefix('suppliers')->name('suppliers.')->group(function (): void {
                Route::get('/', [SupplierController::class, 'index'])->name('index');
                Route::get('/{supplier}', [SupplierController::class, 'show'])->name('show');
            });

        Route::middleware('permission:view-products')
            ->prefix('locations')->name('locations.')->group(function (): void {
                Route::get('/', [StockLocationController::class, 'index'])->name('index');
            });
    });

Route::middleware(['auth', 'active', 'permission:manage-products'])
    ->prefix('inventory')->name('inventory.')->group(function (): void {
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::patch('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::get('/locations/create', [StockLocationController::class, 'create'])->name('locations.create');
        Route::post('/locations', [StockLocationController::class, 'store'])->name('locations.store');
        Route::get('/locations/{location}/edit', [StockLocationController::class, 'edit'])->name('locations.edit');
        Route::patch('/locations/{location}', [StockLocationController::class, 'update'])->name('locations.update');
    });

Route::middleware(['auth', 'active', 'permission:manage-suppliers'])
    ->prefix('inventory')->name('inventory.')->group(function (): void {
        Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::patch('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    });

Route::middleware(['auth', 'active', 'permission:record-stock-movements'])
    ->prefix('inventory')->name('inventory.')->group(function (): void {
        Route::post('/products/{product}/purchase', [StockOperationController::class, 'purchase'])->name('products.purchase');
        Route::post('/products/{product}/consume', [StockOperationController::class, 'consume'])->name('products.consume');
        Route::post('/products/{product}/transfer', [StockOperationController::class, 'transfer'])->name('products.transfer');
    });

Route::middleware(['auth', 'active', 'permission:manage-products'])
    ->prefix('inventory')->name('inventory.')->group(function (): void {
        Route::post('/products/{product}/adjust', [StockOperationController::class, 'adjust'])->name('products.adjust');
    });
