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

/**
 * API Routes - Admin Protected Routes
 *
 * This route group is protected using two middlewares:
 *  - "auth:sanctum": Ensures the user is authenticated via Laravel Sanctum.
 *  - "role:admin": Ensures the authenticated user has an "admin" role.
 *
 * This safeguard ensures that only authorized admins can access these routes.
 *
 */
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

});

/**
 * API Routes Documentation:
 *
 * This route group is protected by:
 * - "auth:sanctum": Ensures the user is authenticated using Laravel Sanctum.
 * - "role:customer": Ensures that the authenticated user has the "customer" role.
 *
 *  This safeguard ensures that only authorized customers can access these routes.
 */
Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

});

/**
 * Grouping routes that require authentication via Sanctum and the "employee" role.
 *
 * Routes within this group are protected by middleware which ensures:
 * - The user is authenticated (using Sanctum).
 * - The authenticated user has the "employee" role.
 *
 * This safeguard ensures that only authorized employees can access these routes.
 *
 */
Route::middleware(['auth:sanctum', 'role:employee'])->group(function () {


    Route::post('/logout', [AuthController::class, 'logout']);
});





/**
 * API Authentication Endpoints
 *
 * 1. POST /register
 *    - Controller method: AuthController::customerRegistration
 *    - Purpose: Registers a new customer.
 *
 * 2. POST /register/employee
 *    - Controller method: AuthController::employeeRegistration
 *    - Purpose: Registers a new employee.
 *
 * 3. POST /login
 *    - Controller method: AuthController::login
 *    - Purpose: Logs in a user.
 */
Route::post('/register', [AuthController::class, 'customerRegistration']);
Route::post('/register/employee', [AuthController::class, 'employeeRegistration']);
Route::post('/login', [AuthController::class, 'login']);



