<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
};

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
    
    Route::post('/logout', [AuthController::class, 'logout']);
});

// customer registration route
Route::post('/register', [AuthController::class, 'customerRegistration']);

// employee registration route
Route::post('/register/employee', [AuthController::class, 'employeeRegistration']);

// login route
Route::post('/login', [AuthController::class, 'login']);

//logout route
