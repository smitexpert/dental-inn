<?php

declare(strict_types=1);

use App\Http\Controllers\Doctors\DoctorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'permission:view-doctors'])
    ->prefix('doctors')->name('doctors.')->group(function (): void {
        Route::get('/', [DoctorController::class, 'index'])->name('index');
        Route::get('/{doctor}', [DoctorController::class, 'show'])->name('show');
    });

Route::middleware(['auth', 'active', 'permission:manage-doctors'])
    ->prefix('doctors')->name('doctors.')->group(function (): void {
        Route::get('/create', [DoctorController::class, 'create'])->name('create');
        Route::post('/', [DoctorController::class, 'store'])->name('store');
        Route::get('/{doctor}/edit', [DoctorController::class, 'edit'])->name('edit');
        Route::patch('/{doctor}', [DoctorController::class, 'update'])->name('update');
        Route::post('/{doctor}/toggle-availability', [DoctorController::class, 'toggleAvailability'])->name('toggle');
    });
