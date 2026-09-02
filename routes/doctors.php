<?php

declare(strict_types=1);

use App\Http\Controllers\Doctors\DoctorController;
use App\Http\Controllers\Doctors\DoctorScheduleController;
use App\Http\Controllers\Doctors\DoctorTimeOffController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'permission:view-doctors'])
    ->prefix('doctors')->name('doctors.')->group(function (): void {
        Route::get('/', [DoctorController::class, 'index'])->name('index');
        Route::get('/{doctor}', [DoctorController::class, 'show'])
            ->whereNumber('doctor')
            ->name('show');
    });

Route::middleware(['auth', 'active', 'permission:manage-doctors'])
    ->prefix('doctors')->name('doctors.')->group(function (): void {
        Route::get('/create', [DoctorController::class, 'create'])->name('create');
        Route::post('/', [DoctorController::class, 'store'])->name('store');
        Route::get('/{doctor}/edit', [DoctorController::class, 'edit'])
            ->whereNumber('doctor')
            ->name('edit');
        Route::patch('/{doctor}', [DoctorController::class, 'update'])
            ->whereNumber('doctor')
            ->name('update');
        Route::post('/{doctor}/toggle-availability', [DoctorController::class, 'toggleAvailability'])
            ->whereNumber('doctor')
            ->name('toggle');
        Route::patch('/{doctor}/schedule', [DoctorScheduleController::class, 'update'])
            ->whereNumber('doctor')
            ->name('schedule.update');
        Route::post('/{doctor}/time-off', [DoctorTimeOffController::class, 'store'])
            ->whereNumber('doctor')
            ->name('time-off.store');
        Route::delete('/{doctor}/time-off/{timeOff}', [DoctorTimeOffController::class, 'destroy'])
            ->whereNumber('doctor')
            ->name('time-off.destroy');
    });
