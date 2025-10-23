<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompteController;

Route::prefix('comptes')->group(function (){
     Route::get('/', [CompteController::class, 'index']);
});