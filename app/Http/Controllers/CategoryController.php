<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CategoryController extends Controller
{

    /**
     * Retrieve a paginated list of categories, including the count of associated services.
     *
     * This method performs the following actions:
     * - Loads categories along with a count of their related services.
     * - Paginates the results to show 15 categories per page.
     * - Returns a JSON response with a 200 status code on successful retrieval.
     *
     * In the event of an error:
     * - Logs the error message and stack trace.
     * - Returns a JSON response with a 500 status code indicating failure.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Throwable If an unexpected error occurs during execution.
     */
    public function index(): JsonResponse
    {
        try {
            $categories = Category::withCount('services')
                                  ->paginate(15);

            return response()->json($categories, 200);

        } catch (\Throwable $e) {
            Log::error('CategoryController@index error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve categories.'
            ], 500);
        }
    }


    /**
     * Store a new category.
     *
     * This method validates the request data for a category, ensuring that the 'name'
     * field is provided, is a string, does not exceed 255 characters, and is unique in
     * the categories table. The 'description' field is optional.
     *
     * The category creation is wrapped inside a database transaction. If the transaction
     * is successful, a JSON response with the newly created category and a HTTP 201 status
     * is returned. In case of any exception or error during the creation process, the error
     * is logged, and a JSON response with an HTTP 500 status is returned.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming HTTP request containing category data.
     * @return \Illuminate\Http\JsonResponse          The JSON response indicating success or failure.
     *
     * @throws \Throwable If an error occurs during the database transaction.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
        ]);

        try {
            $category = DB::transaction(function () use ($data) {
                return Category::create($data);
            });

            return response()->json([
                'message'  => 'Category created successfully.',
                'category' => $category,
            ], 201);

        } catch (\Throwable $e) {
            Log::error('CategoryController@store error', [
                'input'   => $data,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to create category.'
            ], 500);
        }
    }


    /**
     * Retrieve a specific category along with its related services.
     *
     * This method attempts to load the category specified by the given ID, including its
     * associated services with only the 'id', 'name', and 'category_id' fields. If the category
     * is successfully retrieved, a JSON response with a 200 status code containing the category data
     * is returned.
     *
     * If no category is found, a ModelNotFoundException is caught and a JSON response with a 404 status
     * code and an appropriate error message is returned. In the event of other exceptions, the error is
     * logged and a 500 status code with a general failure message is returned.
     *
     * @param string $id The unique identifier of the category.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response containing the category information or error message.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the category cannot be found.
     * @throws \Throwable For any other exceptional errors during the process.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $category = Category::with(['services:id,name,category_id'])
                                 ->findOrFail($id);

            return response()->json($category, 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Category not found.'
            ], 404);

        } catch (\Throwable $e) {
            Log::error('CategoryController@show error', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve category.'
            ], 500);
        }
    }


    /**
     * Update the specified category.
     *
     * This method validates the incoming request data for updating a category,
     * retrieves the target category by its ID, and then applies the update within a database transaction.
     * On success, it returns a JSON response with a success message and the updated category data.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request containing update data.
     * @param string $id The unique identifier of the category to update.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the update result.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the category with the given ID is not found.
     * @throws \Throwable If any error occurs during the update process.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'name'        => "sometimes|required|string|max:255|unique:categories,name,{$id}",
            'description' => 'nullable|string',
        ]);

        try {
            $category = Category::findOrFail($id);

            DB::transaction(function () use ($category, $data) {
                $category->update($data);
            });

            return response()->json([
                'message'  => 'Category updated successfully.',
                'category' => $category->fresh(),
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Category not found.'
            ], 404);

        } catch (\Throwable $e) {
            Log::error('CategoryController@update error', [
                'id'      => $id,
                'input'   => $data,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to update category.'
            ], 500);
        }
    }


    /**
     * Remove the specified category.
     *
     * This method attempts to locate a category using the provided ID. If the category is found,
     * it performs a deletion and returns a JSON response with a success message and a 204 (No Content)
     * HTTP status code. If the category is not found, it catches the ModelNotFoundException and returns
     * a JSON response with a 404 (Not Found) status code along with an error message.
     *
     * Any other exceptions are caught, logged with a detailed error message and trace, and a JSON
     * response with a 500 (Internal Server Error) status code is returned.
     *
     * @param string $id The identifier of the category to be deleted.
     * @return JsonResponse A response indicating whether the deletion was successful or not.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $category = Category::findOrFail($id);
            $category->delete();

            // 204 No Content
            return response()->json([
                'message' => 'Category deleted successfully.'
            ], 204);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Category not found.'
            ], 404);

        } catch (\Throwable $e) {
            Log::error('CategoryController@destroy error', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to delete category.'
            ], 500);
        }
    }
}
