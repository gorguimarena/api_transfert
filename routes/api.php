<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompteController;

Route::middleware('api')->group(function () {

    Route::prefix('V1')->group(function () {
        Route::prefix('comptes')->group(function () {
            Route::get('/', [CompteController::class, 'index']);
        });
    });
});

