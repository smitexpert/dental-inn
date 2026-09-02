<?php

declare(strict_types=1);

use App\Http\Controllers\Appointments\AppointmentController;
use App\Http\Controllers\Appointments\AppointmentStatusController;
use App\Http\Controllers\Appointments\AvailableSlotsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'permission:view-appointments'])
    ->prefix('appointments')->name('appointments.')->group(function (): void {
        Route::get('/', [AppointmentController::class, 'index'])->name('index');
        Route::get('/calendar', [AppointmentController::class, 'calendar'])->name('calendar');
        Route::get('/api/list', [AppointmentController::class, 'apiList'])->name('api.list');
        Route::get('/slots/{doctor}', [AvailableSlotsController::class, 'index'])->name('slots');
        Route::get('/{appointment}', [AppointmentController::class, 'show'])
            ->whereNumber('appointment')
            ->name('show');
    });

Route::middleware(['auth', 'active', 'permission:manage-appointments'])
    ->prefix('appointments')->name('appointments.')->group(function (): void {
        Route::get('/create', [AppointmentController::class, 'create'])->name('create');
        Route::post('/', [AppointmentController::class, 'store'])->name('store');
        Route::get('/{appointment}/edit', [AppointmentController::class, 'edit'])
            ->whereNumber('appointment')
            ->name('edit');
        Route::patch('/{appointment}', [AppointmentController::class, 'update'])
            ->whereNumber('appointment')
            ->name('update');
        Route::delete('/{appointment}', [AppointmentController::class, 'destroy'])
            ->whereNumber('appointment')
            ->name('destroy');
        Route::patch('/{appointment}/status', [AppointmentStatusController::class, 'update'])
            ->whereNumber('appointment')
            ->name('status.update');
    });
