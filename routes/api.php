<?php

use App\Http\Controllers\Api\V1\ClientController;
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
    });
