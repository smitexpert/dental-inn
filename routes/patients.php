<?php

declare(strict_types=1);

use App\Http\Controllers\Patients\PatientController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'permission:view-patients'])
    ->prefix('patients')->name('patients.')->group(function (): void {
        Route::get('/', [PatientController::class, 'index'])->name('index');
        Route::get('/api/search', [PatientController::class, 'search'])->name('api.search');
        Route::get('/{patient}', [PatientController::class, 'show'])->name('show');
    });

Route::middleware(['auth', 'active', 'permission:manage-patients'])
    ->prefix('patients')->name('patients.')->group(function (): void {
        Route::get('/create', [PatientController::class, 'create'])->name('create');
        Route::post('/', [PatientController::class, 'store'])->name('store');
        Route::post('/quick-store', [PatientController::class, 'quickStore'])->name('quick-store');
        Route::get('/{patient}/edit', [PatientController::class, 'edit'])->name('edit');
        Route::patch('/{patient}', [PatientController::class, 'update'])->name('update');
        Route::delete('/{patient}', [PatientController::class, 'destroy'])->name('destroy');
    });
