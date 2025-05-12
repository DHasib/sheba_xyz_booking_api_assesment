<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DiscountController extends Controller
{

    /**
     * Display a listing of discounts.
     *
     * This method retrieves all discounts along with their associated services,
     * ordering them by the 'start_date' in descending order. The resulting list
     * of discounts is then returned as a JSON response, which includes a success
     * flag and the discount data.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response containing the success status and discount data.
     */
    public function index()
    {
        $discounts = Discount::with('services')
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $discounts,
        ], 200);
    }


    /**
     * Store a new discount in the database.
     *
     * This method validates the incoming HTTP request to ensure that the necessary data for creating a discount
     * is provided and meets the defined rules:
     *   - code: Required, must be a string with a maximum length of 50 characters and unique in the
     *           'discounts' table.
     *   - type: Required, must be either 'fixed' or 'percentage'.
     *   - value: Required, must be a numeric value greater than or equal to 0.
     *   - start_date: Required, must be a valid date.
     *   - end_date: Required, must be a valid date that is the same as or later than the start_date.
     *
     * Once the data is validated, a new discount record is created using the validated data.
     * The method then returns a JSON response containing:
     *   - success: A boolean indicating the success of the operation.
     *   - data: The newly created discount record.
     *   - message: A message confirming that the discount was created successfully.
     *
     * @param Illuminate\Http\Request $request The HTTP request instance containing the discount data.
     *
     * @return \Illuminate\Http\JsonResponse JSON response indicating the result of the discount creation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'       => ['required','string','max:50', Rule::unique('discounts','code')],
            'type'       => ['required','in:fixed,percentage'],
            'value'      => ['required','numeric','min:0'],
            'start_date' => ['required','date'],
            'end_date'   => ['required','date','after_or_equal:start_date'],
        ]);

        $discount = Discount::create($validated);

        return response()->json([
            'success' => true,
            'data'    => $discount,
            'message' => 'Discount created successfully.',
        ], 201);
    }


    /**
     * Display the specified discount along with its associated services.
     *
     * This method retrieves a discount record by its unique identifier. It also loads the related services
     * using Eloquent's "with" method. If the discount is found, it returns a JSON response with the discount data
     * and a success flag, along with an HTTP status code 200. If the discount does not exist, it catches the
     * ModelNotFoundException and returns a JSON response indicating that the discount was not found,
     * along with an HTTP status code 404.
     *
     * @param string $id The unique identifier of the discount.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing either the discount data or an error message.
     */
    public function show(string $id)
    {
        try {
            $discount = Discount::with('services')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data'    => $discount,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Discount not found.',
            ], 404);
        }
    }

    /**
     * Updates the specified discount.
     *
     * This method attempts to find a Discount model by its identifier and update it using the validated data
     * provided in the request. The validation rules enforce:
     * - The 'code' field must be a string with a maximum length of 50 characters and unique among discounts,
     *   except for the current discount being updated.
     * - The 'type' field must be either 'fixed' or 'percentage'.
     * - The 'value' field must be a numeric value greater than or equal to 0.
     * - The 'start_date' field must be a valid date.
     * - The 'end_date' field must be a valid date that is the same as or after the 'start_date'.
     *
     * On successful update, the method returns a JSON response with the discount data and a success message.
     * If the discount is not found, a JSON response with an error message and a 404 status is returned.
     *
     * @param \Illuminate\Http\Request $request The incoming request containing discount data.
     * @param string $id The unique identifier for the discount to update.
     *
     * @return \Illuminate\Http\JsonResponse JSON response with the status, discount data, and a message.
     */
    public function update(Request $request, string $id)
    {
        try {
            $discount = Discount::findOrFail($id);

            $validated = $request->validate([
                'code'       => ['sometimes','string','max:50', Rule::unique('discounts','code')->ignore($discount->id)],
                'type'       => ['sometimes','in:fixed,percentage'],
                'value'      => ['sometimes','numeric','min:0'],
                'start_date' => ['sometimes','date'],
                'end_date'   => ['sometimes','date','after_or_equal:start_date'],
            ]);

            $discount->update($validated);

            return response()->json([
                'success' => true,
                'data'    => $discount,
                'message' => 'Discount updated successfully.',
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Discount not found.',
            ], 404);
        }
    }


    /**
     * Remove the specified discount from storage.
     *
     * This method attempts to find a discount by its unique identifier. If the discount is found,
     * it deletes the discount and returns a JSON response indicating success. If the discount is not
     * found, it catches the ModelNotFoundException and returns a JSON response with an error message.
     *
     * @param string $id The unique identifier of the discount to be deleted.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response indicating whether the deletion was successful.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When the discount with the given ID does not exist.
     */
    public function destroy(string $id)
    {
        try {
            $discount = Discount::findOrFail($id);
            $discount->delete();

            return response()->json([
                'success' => true,
                'message' => 'Discount deleted successfully.',
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Discount not found.',
            ], 404);
        }
    }
}
