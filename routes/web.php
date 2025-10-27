<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/storage/api-docs/api-docs.json', function () {
    return response()->file(storage_path('api-docs/api-docs.json'));
});
