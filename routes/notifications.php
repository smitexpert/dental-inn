<?php

declare(strict_types=1);

use App\Http\Controllers\Notifications\LogController;
use App\Http\Controllers\Notifications\SendController;
use App\Http\Controllers\Notifications\TemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', fn () => redirect()->route('notifications.templates.index'));

    Route::get('templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::get('templates/create', [TemplateController::class, 'create'])->name('templates.create');
    Route::post('templates', [TemplateController::class, 'store'])->name('templates.store');
    Route::get('templates/{template}/edit', [TemplateController::class, 'edit'])->name('templates.edit');
    Route::patch('templates/{template}', [TemplateController::class, 'update'])->name('templates.update');
    Route::delete('templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');
    Route::post('templates/{template}/preview', [TemplateController::class, 'preview'])->name('templates.preview');

    Route::get('logs', [LogController::class, 'index'])->name('logs.index');

    Route::get('send', [SendController::class, 'create'])->name('send.create');
    Route::post('send', [SendController::class, 'store'])->name('send.store');
});
