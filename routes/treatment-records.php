<?php

declare(strict_types=1);

use App\Http\Controllers\Patients\TreatmentRecordController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'permission:view-treatment-records'])
    ->prefix('patients/{patient}/treatment-records')
    ->name('patients.treatment-records.')
    ->group(function (): void {
        Route::get('/', [TreatmentRecordController::class, 'index'])->name('index');
        Route::get('/{record}', [TreatmentRecordController::class, 'show'])->name('show');
    });

Route::middleware(['auth', 'active', 'permission:manage-treatment-records'])
    ->prefix('patients/{patient}/treatment-records')
    ->name('patients.treatment-records.')
    ->group(function (): void {
        Route::get('/create', [TreatmentRecordController::class, 'create'])->name('create');
        Route::post('/', [TreatmentRecordController::class, 'store'])->name('store');
        Route::get('/{record}/edit', [TreatmentRecordController::class, 'edit'])->name('edit');
        Route::patch('/{record}', [TreatmentRecordController::class, 'update'])->name('update');
        Route::post('/{record}/complete', [TreatmentRecordController::class, 'complete'])->name('complete');
        Route::post('/{record}/cancel', [TreatmentRecordController::class, 'cancel'])->name('cancel');
    });
