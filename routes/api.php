<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Pradeepdev\EnvironmentManager\Http\Controllers\ApiController;

$prefix = config('environment-manager.api_prefix', 'api/env-manager');
$guard  = trim((string) config('environment-manager.guard', 'admin'));

$defaultMiddleware = ['api', $guard !== '' ? "auth:{$guard}" : 'auth'];
$middleware        = config('environment-manager.api_middleware', $defaultMiddleware) ?? $defaultMiddleware;
$rateLimit         = config('environment-manager.api_rate_limit', 60);

Route::prefix($prefix)
    ->middleware([...$middleware, "throttle:{$rateLimit},1"])
    ->name('env-manager.api.')
    ->group(function () {
        Route::get('/env', [ApiController::class, 'index'])->name('index');
        Route::post('/env', [ApiController::class, 'store'])->name('store');
        Route::put('/env/{key}', [ApiController::class, 'update'])->name('update');
        Route::delete('/env/{key}', [ApiController::class, 'destroy'])->name('destroy');
        Route::get('/env/history', [ApiController::class, 'history'])->name('history');
        Route::get('/env/backups', [ApiController::class, 'backups'])->name('backups');
    });
