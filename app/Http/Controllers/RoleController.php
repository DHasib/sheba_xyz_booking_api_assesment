<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoleController extends Controller
{

    /**
     * Display a paginated list of roles with user count.
     *
     * This method retrieves roles along with the number of associated users,
     * paginates the result (50 per page), and returns a JSON response containing the data.
     * If an error occurs during retrieval, it logs the error details and returns a JSON response
     * with a generic error message and a 500 status code.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response containing roles data on success or an error message on failure.
     */
    public function index(): JsonResponse
    {
        try {
            $roles = Role::withCount('users')->paginate(50);

            return response()->json($roles, 200);
        } catch (\Throwable $e) {
            Log::error('RoleController@index error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve roles.'
            ], 500);
        }
    }


    /**
     * Store a new role.
     *
     * This method validates the incoming request data and attempts to create a new role record.
     * On success, it returns a JSON response with the created role and a success message.
     * On failure, it logs the error and returns a JSON response with an error message.
     *
     * @param Request $request The HTTP request containing role data.
     *
     * @return JsonResponse A JSON response indicating the result of the operation.
     *
     * @throws \Throwable Throws exception if an error occurs during role creation.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string',
        ]);

        try {
            $role = Role::create($data);

            return response()->json([
                'message' => 'Role created successfully.',
                'role'    => $role,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('RoleController@store error', [
                'input'   => $data,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to create role.'
            ], 500);
        }
    }


    /**
     * Display the specified role with its associated users.
     *
     * This method retrieves a role by its unique identifier along with the users related to the role,
     * selecting only the id, name, and email fields for each user. If the role is found, it returns the role data
     * in a JSON response with an HTTP status code of 200. If the role is not found, a JSON response with a 404
     * status code is returned. Any other exceptions are caught, logged, and result in a JSON response with a 500 status code.
     *
     * @param string $id The unique identifier of the role to be retrieved.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response containing the role data or error information.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $role = Role::with(['users:id,name,email,role_id'])->findOrFail($id);

            return response()->json($role, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Role not found.'
            ], 404);
        } catch (\Throwable $e) {
            Log::error('RoleController@show error', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve role.'
            ], 500);
        }
    }


    /**
     * Update the specified role.
     *
     * This method validates the request data and updates the role corresponding to the provided ID.
     * It accepts optional input for the role's name and description, ensuring that the name is unique.
     *
     * @param Request $request The HTTP request instance containing validation data.
     * @param string  $id      The unique identifier of the role to update.
     *
     * @return JsonResponse A JSON response indicating success or failure of the update operation.
     *
     * @throws ModelNotFoundException Thrown when the role with the specified ID is not found.
     * @throws \Throwable             Thrown on any unexpected error during the update process.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'name'        => "sometimes|required|string|max:255|unique:roles,name,{$id}",
            'description' => 'nullable|string',
        ]);

        try {
            $role = Role::findOrFail($id);

            $role->update($data);

            return response()->json([
                'message' => 'Role updated successfully.',
                'role'    => $role,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Role not found.'
            ], 404);
        } catch (\Throwable $e) {
            Log::error('RoleController@update error', [
                'id'      => $id,
                'input'   => $data,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to update role.'
            ], 500);
        }
    }


    /**
     * Deletes a specific role by its identifier.
     *
     * This method attempts to find a role by the given ID and delete it, ensuring that the role is not associated with any users.
     * If the role is linked to users, a JSON response with a 400 status is returned indicating the deletion constraint.
     * If the role is not found, a 404 JSON response is returned.
     * In case of any other error, the exception is logged and a 500 JSON response is provided.
     *
     * @param string $id The unique identifier of the role to be deleted.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response indicating the outcome:
     *         - 204 on successful deletion.
     *         - 400 if the role is assigned to users.
     *         - 404 if the role is not found.
     *         - 500 for any other error encountered during the operation.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);

            if ($role->users()->exists()) {
                return response()->json([
                    'message' => 'Cannot delete role assigned to users.'
                ], 400);
            }

            $role->delete();

            // 204 No Content
            return response()->json([
                'message' => 'Role Deleted successfully.'
            ], 204);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Role not found.'
            ], 404);
        } catch (\Throwable $e) {
            Log::error('RoleController@destroy error', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to delete role.'
            ], 500);
        }
    }
}
