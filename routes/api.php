<?php

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
    ->middleware('throttle:api')
    ->group(function () {
        Route::apiResource('clients', ClientController::class);
        Route::apiResource('services', ServiceController::class);

        Route::apiResource('contracts', ContractController::class);
        Route::post('contracts/{contract}/cancel', [ContractController::class, 'cancel'])
            ->name('contracts.cancel');
        Route::post('contracts/{contract}/items', [ContractController::class, 'storeItem'])
            ->name('contracts.items.store');
        Route::delete('contracts/{contract}/items/{item}', [ContractController::class, 'destroyItem'])
            ->name('contracts.items.destroy');
        Route::get('contracts/{contract}/history', [ContractHistoryController::class, 'index'])
            ->name('contracts.history.index');
    });
