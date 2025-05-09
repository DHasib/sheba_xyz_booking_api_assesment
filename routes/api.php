<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    ServiceController,
    RoleController,
    CategoryController,
    BookingController
};


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

    Route::apiResource('services', ServiceController::class);
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('bookings', BookingController::class)->except(['store']);
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

    Route::apiResource('bookings', BookingController::class)->only(['store', 'update', 'destroy']);
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


});





/**
 * API Routes - Public Endpoints
 *
 * This set of routes is defined without applying the 'auth:sanctum' and 'RoleCheck' middleware,
 * making these endpoints publicly accessible.
 *
 * Endpoints:
 *   - POST /register
 *       -> Registers a new customer (handled by AuthController::customerRegistration).
 *
 *   - POST /register/employee
 *       -> Registers a new employee (handled by AuthController::employeeRegistration).
 *
 *   - POST /login
 *       -> Authenticates a user and provides login functionality (handled by AuthController::login).
 *
 *   - GET /services
 *       -> Retrieves a list of services (handled by ServiceController::index).
 *
 *   - GET /services/{service}
 *       -> Retrieves details of a specific service (handled by ServiceController::show).
 *
 * Note:
 *   The "services" resource routes are limited to only the 'index' and 'show' actions.
 */
Route::withoutMiddleware(['auth:sanctum', RoleCheck::class,])->group(function () {
    Route::post('/register', [AuthController::class, 'customerRegistration']);
    Route::post('/register/employee', [AuthController::class, 'employeeRegistration']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::apiResource('services', ServiceController::class)->only(['index', 'show']);
});


/**
 * POST /logout
 *
 * This endpoint logs out the authenticated user by calling the logout method in the AuthController.
 * It ensures that only authenticated users (using the 'auth:sanctum' middleware) can access this route.
 *
 */
Route::post('/logout', [AuthController::class, 'logout'])
     ->middleware('auth:sanctum');




