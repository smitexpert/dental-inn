<?php

declare(strict_types=1);

use App\Http\Controllers\Patients\TreatmentRecordController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'permission:view-treatment-records'])
    ->prefix('patients/{patient}/treatment-records')
    ->name('patients.treatment-records.')
    ->group(function (): void {
        Route::get('/', [TreatmentRecordController::class, 'index'])
            ->whereNumber('patient')
            ->name('index');
        Route::get('/{record}', [TreatmentRecordController::class, 'show'])
            ->whereNumber('patient')
            ->whereNumber('record')
            ->name('show');
    });

Route::middleware(['auth', 'active', 'permission:manage-treatment-records'])
    ->prefix('patients/{patient}/treatment-records')
    ->name('patients.treatment-records.')
    ->group(function (): void {
        Route::get('/create', [TreatmentRecordController::class, 'create'])
            ->whereNumber('patient')
            ->name('create');
        Route::post('/', [TreatmentRecordController::class, 'store'])
            ->whereNumber('patient')
            ->name('store');
        Route::get('/{record}/edit', [TreatmentRecordController::class, 'edit'])
            ->whereNumber('patient')
            ->whereNumber('record')
            ->name('edit');
        Route::patch('/{record}', [TreatmentRecordController::class, 'update'])
            ->whereNumber('patient')
            ->whereNumber('record')
            ->name('update');
        Route::post('/{record}/complete', [TreatmentRecordController::class, 'complete'])
            ->whereNumber('patient')
            ->whereNumber('record')
            ->name('complete');
        Route::post('/{record}/cancel', [TreatmentRecordController::class, 'cancel'])
            ->whereNumber('patient')
            ->whereNumber('record')
            ->name('cancel');
    });
