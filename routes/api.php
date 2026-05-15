<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ContractController;
use App\Http\Controllers\Api\V1\ContractHistoryController;
use App\Http\Controllers\Api\V1\ServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function () {
        Route::post('auth/login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('auth.login');

        Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
            Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
            Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');

            Route::apiResource('clients', ClientController::class)
                ->only(['index', 'show', 'update', 'destroy']);
            Route::post('clients', [ClientController::class, 'store'])
                ->middleware(['throttle:10,1', 'idempotency'])
                ->name('clients.store');

            Route::apiResource('services', ServiceController::class);

            Route::apiResource('contracts', ContractController::class)
                ->only(['index', 'show', 'update', 'destroy']);
            Route::post('contracts', [ContractController::class, 'store'])
                ->middleware(['throttle:10,1', 'idempotency'])
                ->name('contracts.store');
            Route::post('contracts/{contract}/cancel', [ContractController::class, 'cancel'])
                ->middleware('throttle:5,1')
                ->name('contracts.cancel');
            Route::post('contracts/{contract}/items', [ContractController::class, 'storeItem'])
                ->middleware(['throttle:30,1', 'idempotency'])
                ->name('contracts.items.store');
            Route::delete('contracts/{contract}/items/{item}', [ContractController::class, 'destroyItem'])
                ->name('contracts.items.destroy');
            Route::get('contracts/{contract}/history', [ContractHistoryController::class, 'index'])
                ->name('contracts.history.index');
        });
    });
