<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Pradeepdev\EnvironmentManager\Http\Controllers\UiController;

$prefix = config('environment-manager.route_prefix', 'admin/env-manager');
$guard  = trim((string) config('environment-manager.guard', 'admin'));

$defaultMiddleware = ['web', $guard !== '' ? "auth:{$guard}" : 'auth'];
$middleware        = config('environment-manager.route_middleware', $defaultMiddleware) ?? $defaultMiddleware;

Route::prefix($prefix)
    ->middleware($middleware)
    ->name('env-manager.')
    ->group(function () {
        // Variable management
        Route::get('/', [UiController::class, 'index'])->name('index');
        Route::get('/create', [UiController::class, 'create'])->name('create');
        Route::post('/', [UiController::class, 'store'])->name('store');
        Route::get('/{key}/edit', [UiController::class, 'edit'])->name('edit');
        Route::put('/{key}', [UiController::class, 'update'])->name('update');
        Route::delete('/{key}', [UiController::class, 'destroy'])->name('destroy');

        // Reveal secret (AJAX)
        Route::post('/{key}/reveal', [UiController::class, 'reveal'])->name('reveal');

        // History & Audit
        Route::get('/history', [UiController::class, 'history'])->name('history');
        Route::get('/audit-log', [UiController::class, 'auditLog'])->name('audit-log');

        // Diff viewer
        Route::get('/diff', [UiController::class, 'diff'])->name('diff');

        // Import / Export
        Route::get('/import-export', [UiController::class, 'importExport'])->name('import-export');
        Route::get('/export/{format}', [UiController::class, 'export'])->name('export');
        Route::post('/import', [UiController::class, 'importStore'])->name('import');

        // Backups
        Route::get('/backups', [UiController::class, 'backups'])->name('backups');
        Route::post('/backups', [UiController::class, 'createBackup'])->name('backups.create');
        Route::get('/backups/{filename}/download', [UiController::class, 'downloadBackup'])->name('backups.download');
        Route::post('/backups/{filename}/restore', [UiController::class, 'restoreBackup'])->name('backups.restore');
        Route::delete('/backups/{filename}', [UiController::class, 'deleteBackup'])->name('backups.delete');
    });
