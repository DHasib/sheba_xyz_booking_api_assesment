<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{

    /**
     * Handles customer registration by validating the input data and creating a new user with a "customer" role.
     *
     * This method validates incoming request data to ensure that it contains all required fields:
     * - name: Required string between 3 and 100 characters.
     * - email: Required valid email address that must be unique in the users table.
     * - address: Optional string with a maximum of 255 characters.
     * - phone: Required string with a maximum of 15 characters.
     * - password: Required, must be confirmed, at least 8 characters long, include mixed case letters and symbols.
     *
     * Upon successful validation, the method retrieves the "customer" role, and creates the user data accordingly.
     *
     * @param Request $request An instance of the HTTP request containing registration input data.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response with the created user payload and a 201 HTTP status on success.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the "customer" role is not found.
     * @throws \Throwable For any other exceptions, logs the error and returns a 500 HTTP status with an appropriate message.
     */

    public function customerRegistration(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|min:3|max:100',
            'email'    => 'required|email|unique:users,email',
            'address'  => 'nullable|string|max:255',
            'phone'    => 'required|string|max:15',
            'password' => [
                            'required',
                            'string',
                            'confirmed',
                            Password::min(8)
                                    ->mixedCase()
                                    ->symbols()
                                    // ->uncompromised(),
                        ],
       ]);

        try {
                $role = Role::where('name', 'customer')->firstOrFail();

                 $payload = $this->storeUserData($data, $role->id);

                return response()->json($payload, 201);

            } catch (ModelNotFoundException $e) {
                return response()->json([
                    'message' => 'Customer role not configured. Please contact support.',
                ], 404);

            } catch (\Throwable $e) {
                Log::error('customerRegistration error', [
                    'error'     => $e->getMessage(),
                    'trace'     => $e->getTraceAsString(),
                    'input'     => array_diff_key($data, ['password' => '']),
                ]);
                return response()->json([
                    'message' => "Registration failed. Please try again later.",
                ], 500);
            }
    }

    /**
     * Handle employee registration.
     *
     * This method validates the incoming request data for employee registration with the following criteria:
     * - name: required, must be a string between 3 and 100 characters.
     * - email: required, must be a valid email format, and unique in the users table.
     * - address: optional, if provided must be a string up to 255 characters.
     * - phone: required, must be a string with a maximum length of 15 characters and unique in the users table.
     * - password: required, must be a string, confirmed, and meet password security requirements including:
     *     - minimum length of 8 characters,
     *     - mixed case letters,
     *     - inclusion of symbols.
     * - role_id: required, must exist in the roles table.
     *
     * After successful validation, the user data is stored using the storeUserData method with the provided role_id,
     * and a JSON response containing the stored user data is returned with a HTTP status code 201.
     *
     * @param \Illuminate\Http\Request $request The HTTP request instance containing the registration data.
     *
     * @return \Illuminate\Http\JsonResponse JSON response with the registered user payload and HTTP status code 201.
     */
    public function employeeRegistration(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|min:3|max:100',
            'email'    => 'required|email|unique:users,email',
            'address'  => 'sometimes|string|max:255',
            'phone'    => 'required|string|max:15|unique:users,phone',
            'password' => [
                            'required',
                            'string',
                            'confirmed',
                            Password::min(8)
                                    ->mixedCase()
                                    ->symbols()
                                    // ->uncompromised(),
                        ],
             'role_id' => 'required|exists:roles,id',
       ]);


        $payload = $this->storeUserData($data,  $data['role_id']);

        return response()->json($payload, 201);
    }

    /**
     * Stores a new user's data, assigns a role, and generates an access token.
     *
     * This method wraps the user creation and token generation within a database transaction to ensure
     * atomicity. If an error occurs during the process, it will be caught, logged, and a JSON error response
     * will be returned.
     *
     * @param array $data   An associative array containing user details including:
     *                      - 'name': The name of the user.
     *                      - 'email': The user's email address.
     *                      - 'phone': The user's phone number.
     *                      - 'password': The user's password (which will be hashed).
     *                      - 'address' (optional): The user's address.
     * @param int   $roleId The identifier for the user's role.
     *
     * @return array|\Illuminate\Http\JsonResponse Returns an array containing:
     *                      - 'message': Success message.
     *                      - 'user': An array with user details (id, name, email, address, phone, and role information).
     *                      - 'access_token': The generated access token.
     *                      - 'token_type': The token type (Bearer).
     *                      In case of failure, returns a JSON response with an error message and HTTP status code 500.
     */
    private function storeUserData(array $data, int $roleId)
    {
        try {
                // Wrap in a transaction so we never end up half-done
                $result = DB::transaction(function () use ($data, $roleId) {
                    $user = User::create([
                        'name'     => $data['name'],
                        'email'    => $data['email'],
                        'address'  => $data['address'] ?? null,
                        'phone'    => $data['phone'],
                        'password' => Hash::make($data['password']),
                        'role_id'  => $roleId,
                    ]);

                    $token = $user
                        ->createToken($data['name'], ['*'], now()->addHours(3))
                        ->plainTextToken;

                    $user->load('role');

                    return [
                        'message'      => 'User registered successfully',
                        'user'         => [
                                            'id'        => $user->id,
                                            'name'      => $user->name,
                                            'email'     => $user->email,
                                            'address'   => $user->address,
                                            'phone'     => $user->phone,
                                            'role_id' => $user->role->id,
                                            'role_name' => $user->role->name,
                                        ],
                        'access_token' => $token,
                        'token_type'   => 'Bearer',
                        'expires_at' => now()->addHours(3)->toDateTimeString(),
                    ];
                });

                return $result;


            } catch (\Throwable $e) {
                Log::error('storeUserData failed', [
                    'exception' => $e->getMessage(),
                    'stack'     => $e->getTraceAsString(),
                    'input'     => Arr::except($data, ['password']),
                ]);

                return response()->json([
                    'message' => 'Registration failed. Please try again later.' . $e->getMessage(),
                ], 500);
            }
    }

    /**
     * Handles user login by validating credentials, authenticating the user,
     * creating a personal access token, and returning the user details along with the token.
     *
     * @param Request $request The HTTP request containing the user's email and password.
     *
     * @return JsonResponse A JSON response containing a success message, access token, user details,
     *                      token type, and the token's expiration datetime.
     *
     * @throws ValidationException If the given credentials are invalid.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($data)) {
            throw ValidationException::withMessages([
                'message' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();

        // user with role name
        $user->load('role');

        $token = $user->createToken($user->name, ['*'], now()->addHours(3))->plainTextToken;


        return response()->json([
            'message'      => 'Login successful',
            'access_token' => $token,
            'user'         => [
                                'id'        => $user->id,
                                'name'      => $user->name,
                                'email'     => $user->email,
                                'address'   => $user->address,
                                'phone'     => $user->phone,
                                'role_id' => $user->role->id,
                                'role_name' => $user->role->name,
                            ],
            'token_type'   => 'Bearer',
            'expires_at' => now()->addHours(3)->toDateTimeString(),
        ], Response::HTTP_OK);
    }

    /**
     * Logs out the authenticated user by deleting the current access token.
     *
     * This method retrieves the current user's token from the request and deletes it,
     * effectively logging the user out. It then returns a JSON response confirming the logout.
     *
     * @param \Illuminate\Http\Request $request The HTTP request instance containing the user and token.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response indicating a successful logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ], Response::HTTP_OK);
    }
}
