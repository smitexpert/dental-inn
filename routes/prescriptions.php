<?php

declare(strict_types=1);

use App\Http\Controllers\Prescriptions\MedicineAutocompleteController;
use App\Http\Controllers\Prescriptions\MedicineQuickCreateController;
use App\Http\Controllers\Prescriptions\PrescriptionController;
use App\Http\Controllers\Prescriptions\PrescriptionPrintController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'permission:view-prescriptions'])
    ->prefix('patients/{patient}/prescriptions')
    ->name('patients.prescriptions.')
    ->group(function (): void {
        Route::get('/', [PrescriptionController::class, 'index'])
            ->whereNumber('patient')
            ->name('index');
        Route::get('/{prescription}', [PrescriptionController::class, 'show'])
            ->whereNumber('patient')
            ->whereNumber('prescription')
            ->name('show');
        Route::get('/{prescription}/print', [PrescriptionPrintController::class, 'show'])
            ->whereNumber('patient')
            ->whereNumber('prescription')
            ->name('print');
    });

Route::middleware(['auth', 'active', 'permission:manage-prescriptions'])
    ->prefix('patients/{patient}/prescriptions')
    ->name('patients.prescriptions.')
    ->group(function (): void {
        Route::get('/create', [PrescriptionController::class, 'create'])
            ->whereNumber('patient')
            ->name('create');
        Route::post('/', [PrescriptionController::class, 'store'])
            ->whereNumber('patient')
            ->name('store');
        Route::get('/{prescription}/edit', [PrescriptionController::class, 'edit'])
            ->whereNumber('patient')
            ->whereNumber('prescription')
            ->name('edit');
        Route::patch('/{prescription}', [PrescriptionController::class, 'update'])
            ->whereNumber('patient')
            ->whereNumber('prescription')
            ->name('update');
        Route::post('/{prescription}/issue', [PrescriptionController::class, 'issue'])
            ->whereNumber('patient')
            ->whereNumber('prescription')
            ->name('issue');
        Route::post('/{prescription}/cancel', [PrescriptionController::class, 'cancel'])
            ->whereNumber('patient')
            ->whereNumber('prescription')
            ->name('cancel');
    });

// Medicine catalog endpoints — used by the prescription picker modal.
// Lives under its own prefix so it can be reused from other places later.
Route::middleware(['auth', 'active', 'permission:manage-prescriptions'])
    ->prefix('medicines')
    ->name('medicines.')
    ->group(function (): void {
        Route::get('/search', [MedicineAutocompleteController::class, 'search'])->name('search');
        Route::post('/quick', [MedicineQuickCreateController::class, 'store'])->name('quick.store');
    });