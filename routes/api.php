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
 * API Routes Documentation
 *
 * This group of API routes is protected by Sanctum authentication and a role-based middleware
 * that restricts access to users with the "admin" role.
 *
 * Routes:
 * - Services:
 *     - Provides endpoints to store, update, and destroy service records.
 *     - Only the store, update, and destroy methods are exposed.
 *
 * - Roles:
 *     - Full resource controller endpoints for role management.
 *
 * - Categories:
 *     - Full resource controller endpoints for managing categories.
 *
 * - Bookings:
 *     - Exposes only the index method of the resource controller to list bookings.
 *     - Includes a custom PUT endpoint ('bookings/status/update') to update the status of a booking.
 */
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

    Route::apiResource('services', ServiceController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('bookings', BookingController::class)->only(['index']);
    Route::put('bookings/status/update', [BookingController::class, 'updateBookingStatus']);
});




/**
 * API Routes for Booking Management
 *
 * This route group applies the following middleware:
 * - auth:sanctum: Ensures the user is authenticated via Sanctum.
 * - role:customer: Verifies that the authenticated user has the 'customer' role.
 *
 * Within this group, an API resource route for the 'bookings' endpoint is defined,
 * but only the following actions are available:
 * - store: To create a new booking.
 * - update: To modify an existing booking.
 * - destroy: To delete an existing booking.
 *
 * The routes are defined in the API routes file located at:
 * /c:/Users/dkhas/Desktop/sheba_xyz_booking_api_assesment_mdhasib522@gmail.com/routes/api.php.
 */
Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {

    Route::apiResource('bookings', BookingController::class)->only(['store', 'update', 'destroy']);
});





/**
 * API Route Definitions with Middleware Protection
 *
 * This group of routes applies the following middleware:
 *   - auth:sanctum: Ensures that the request is authenticated using Laravel Sanctum.
 *   - role:customer,admin: Restricts access to users that have either the "customer" or "admin" role.
 *
 * Routes Included:
 *   - Bookings:
 *       * Endpoint: /bookings/{booking}
 *       * HTTP Verb: GET (show)
 *       * Controller: BookingController
 *       * Description: Retrieves details of a specific booking.
 *
 *   - Services:
 *       * Endpoints:
 *            - /services (index)
 *            - /services/{service} (show)
 *       * HTTP Verbs: GET (index, show)
 *       * Controller: ServiceController
 *       * Description: Retrieves a list of services or details of a specific service.
 */
Route::middleware(['auth:sanctum', 'role:customer,admin'])->group(function () {

    Route::apiResource('bookings', BookingController::class)->only(['show']);
    Route::apiResource('services', ServiceController::class)->only(['index', 'show']);

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
 * API Routes without Authentication and Role Middleware
 *
 * This file defines public API endpoints that bypass the default middleware
 * protections such as 'auth:sanctum' and 'RoleCheck'. These endpoints are intended
 * for actions where user authentication is not required.
 *
 * Endpoints:
 *
 * 1. Registration and Login:
 *    - POST /register
 *      - Endpoint to register a new customer.
 *
 *    - POST /register/employee
 *      - Endpoint to register a new employee.
 *
 *    - POST /login
 *      - Endpoint for users to login.
 *
 * 2. Services:
 *    - API Resource for 'services' limited to index and show methods:
 *      - GET /services
 *         - Retrieves a list of services.
 *      - GET /services/{id}
 *         - Retrieves details for a specific service identified by its ID.
 *
 * 3. Bookings:
 *    - GET /bookings/status/{uniqueId}
 *      - Retrieves the current booking status using a unique identifier.
 *
 * Note:
 * All of these endpoints are grouped together to explicitly bypass the typical
 * authentication and role-checking middleware. Use caution when exposing such
 * routes in production environments.
 */
Route::withoutMiddleware(['auth:sanctum', RoleCheck::class,])->group(function () {
    Route::post('/register', [AuthController::class, 'customerRegistration']);
    Route::post('/register/employee', [AuthController::class, 'employeeRegistration']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::apiResource('services', ServiceController::class)->only(['index', 'show']);


    Route::get('bookings/status/{uniqueId}', [BookingController::class, 'getBookingStatusByUniqueId']);
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




