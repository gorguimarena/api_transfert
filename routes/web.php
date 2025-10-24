<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/docs/asset/{file}', function ($file) {
    $path = base_path('vendor/swagger-api/swagger-ui/dist/' . $file);
    if (file_exists($path)) {
        return response()->file($path);
    }
    abort(404);
})->where('file', '.*');
