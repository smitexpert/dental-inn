<?php

use App\Http\Controllers\Settings\ClinicSettingsController;
use App\Http\Controllers\Settings\WorkingHoursController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'permission:manage-settings'])
    ->prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [ClinicSettingsController::class, 'edit'])->name('edit');
        Route::patch('/', [ClinicSettingsController::class, 'update'])->name('update');
        Route::get('/working-hours', [WorkingHoursController::class, 'edit'])->name('hours.edit');
        Route::patch('/working-hours', [WorkingHoursController::class, 'update'])->name('hours.update');
    });
