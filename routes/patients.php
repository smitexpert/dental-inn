<?php

declare(strict_types=1);

use App\Http\Controllers\Patients\PatientController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'permission:view-patients'])
    ->prefix('patients')->name('patients.')->group(function (): void {
        Route::get('/', [PatientController::class, 'index'])->name('index');
        Route::get('/search', [PatientController::class, 'search'])->name('search');
        Route::get('/{patient}', [PatientController::class, 'show'])
            ->whereNumber('patient')
            ->name('show');
    });

Route::middleware(['auth', 'active', 'permission:manage-patients'])
    ->prefix('patients')->name('patients.')->group(function (): void {
        Route::get('/create', [PatientController::class, 'create'])->name('create');
        Route::post('/', [PatientController::class, 'store'])->name('store');
        Route::post('/quick', [PatientController::class, 'quickStore'])->name('quick');
        Route::get('/{patient}/edit', [PatientController::class, 'edit'])
            ->whereNumber('patient')
            ->name('edit');
        Route::patch('/{patient}', [PatientController::class, 'update'])
            ->whereNumber('patient')
            ->name('update');
        Route::delete('/{patient}', [PatientController::class, 'destroy'])
            ->whereNumber('patient')
            ->name('destroy');
    });
