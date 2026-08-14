<?php

declare(strict_types=1);

use App\Http\Controllers\Appointments\AppointmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'permission:view-appointments'])
    ->prefix('appointments')->name('appointments.')->group(function (): void {
        Route::get('/', [AppointmentController::class, 'index'])->name('index');
        Route::get('/calendar', [AppointmentController::class, 'calendar'])->name('calendar');
        Route::get('/{appointment}', [AppointmentController::class, 'show'])->name('show');
    });

Route::middleware(['auth', 'active', 'permission:manage-appointments'])
    ->prefix('appointments')->name('appointments.')->group(function (): void {
        Route::get('/create', [AppointmentController::class, 'create'])->name('create');
        Route::post('/', [AppointmentController::class, 'store'])->name('store');
        Route::get('/{appointment}/edit', [AppointmentController::class, 'edit'])->name('edit');
        Route::patch('/{appointment}', [AppointmentController::class, 'update'])->name('update');
        Route::delete('/{appointment}', [AppointmentController::class, 'destroy'])->name('destroy');
    });
