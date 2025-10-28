<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompteController;
use App\Http\Middleware\LoggingMiddleware;
use App\Http\Middleware\AuthMiddleware;

Route::middleware(['api', LoggingMiddleware::class])->group(function () {

    Route::prefix('v1')->group(function () {
        Route::prefix('auth')->group(function () {

            Route::post('/login', [AuthController::class, 'login']);

            Route::post('/refresh', [AuthController::class, 'refresh']);

            Route::middleware('auth:api')->post('/logout', [AuthController::class, 'logout']);
        });

        Route::prefix('comptes')->group(function () {

            Route::middleware(AuthMiddleware::class)->get('/', [CompteController::class, 'index']);

            Route::middleware(AuthMiddleware::class)->get('/{compte}', [CompteController::class, 'show']);

            Route::middleware(AuthMiddleware::class)->post('/', [CompteController::class, 'store']);

            Route::middleware(AuthMiddleware::class)->post('/{compte}/bloquer', [CompteController::class, 'bloquer']);
        });
    });
});
