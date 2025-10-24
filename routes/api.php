<?php

use App\Http\Controllers\CompteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    Route::prefix('comptes')->group(function () {
        Route::get('/', [CompteController::class, 'index']);
    });

    Route::get('/documentation', function () {
        return view('vendor.l5-swagger.index');
    })->name('l5-swagger.default.api');
});
