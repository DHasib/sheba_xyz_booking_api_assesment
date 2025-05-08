<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {

    return [
        'message' => 'Hello World',
        'status' => 200,
    ];


});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function () {
        return auth()->user();
    });
});
