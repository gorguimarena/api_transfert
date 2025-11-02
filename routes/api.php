<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TransactionController;
use App\Http\Middleware\LoggingMiddleware;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\RoleMiddleware;

Route::middleware(['api', LoggingMiddleware::class])->group(function () {

    Route::prefix('v1')->group(function () {
        Route::prefix('auth')->group(function () {

            Route::post('/login', [AuthController::class, 'login']);

            Route::post('/refresh', [AuthController::class, 'refresh']);

            Route::middleware('auth:api')->post('/logout', [AuthController::class, 'logout']);
        });

        Route::prefix('comptes')->group(function () {

            Route::middleware(AuthMiddleware::class)->get('/', [CompteController::class, 'index']);

            Route::middleware(AuthMiddleware::class)->get('/numero/{numero}', [CompteController::class, 'getByNumero']);

            Route::middleware(AuthMiddleware::class)->get('/{compteId}', [CompteController::class, 'show']);

            Route::middleware([AuthMiddleware::class, RoleMiddleware::class . ':admin'])->post('/', [CompteController::class, 'store']);

            Route::middleware([AuthMiddleware::class, RoleMiddleware::class . ':admin'])->post('/{compteId}/bloquer', [CompteController::class, 'bloquer']);

            // Routes pour les transactions d'un compte spécifique
            Route::middleware(AuthMiddleware::class)->get('/{id}/transactions', [TransactionController::class, 'getByCompte']);

            Route::middleware(AuthMiddleware::class)->get('/{id}/transactions/stats', [TransactionController::class, 'getStats']);
        });

        Route::prefix('clients')->group(function () {

            Route::middleware(AuthMiddleware::class)->get('/telephone/{telephone}', [ClientController::class, 'getByTelephone']);
        });

        // Routes pour les transactions générales
        Route::prefix('transactions')->group(function () {

            Route::middleware([AuthMiddleware::class, RoleMiddleware::class . ':admin'])->get('/', [TransactionController::class, 'index']);

            Route::middleware(AuthMiddleware::class)->get('/{id}', [TransactionController::class, 'show']);

            Route::middleware(AuthMiddleware::class)->post('/', [TransactionController::class, 'store']);

            Route::middleware([AuthMiddleware::class, RoleMiddleware::class . ':admin'])->get('/recentes', [TransactionController::class, 'getRecentes']);
        });
    });
});
