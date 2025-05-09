<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ServiceController extends Controller
{


    /**
     * Store a new service.
     *
     * This method validates the incoming request data, creates a new service record,
     * associates any provided employee IDs with the service, and loads related models
     * (category, discount, and employees). The entire operation is performed within a
     * database transaction to ensure data integrity. If an error occurs during the process,
     * it logs the error and returns an HTTP 500 response.
     *
     * @param Request $request The HTTP request instance containing the service data.
     *
     * @return JsonResponse Returns a JSON response with a success message and the service data on successful creation,
     *                       or an error message on failure.
     *
     * @throws \Throwable If an exception occurs during the database transaction.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id'  => 'required|exists:categories,id',
            'name'         => 'required|string|max:255|unique:services,name',
            'price'        => 'required|numeric|min:0',
            'description'  => 'nullable|string',
            'discount_id'  => 'sometimes|nullable|exists:discounts,id',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'integer|exists:users,id',
        ]);

        try {
            $service = DB::transaction(function () use ($data) {
                $svc = Service::create([
                    'category_id' => $data['category_id'],
                    'name'        => $data['name'],
                    'price'       => $data['price'],
                    'description' => $data['description'] ?? null,
                    'discount_id' => $data['discount_id'] ?? null,
                ]);

                if (! empty($data['employee_ids'])) {
                    $svc->employees()->sync($data['employee_ids']);
                }

                return $svc->load(['category', 'discount', 'employees']);
            });

            return response()->json([
                'message' => 'Service created successfully.',
                'service' => $service,
            ], 201);

        } catch (\Throwable $e) {
            Log::error('ServiceController@store error', [
                'input'   => $data,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to create service.'
            ], 500);
        }
    }


    /**
     * Retrieve a paginated list of services that have active non-confirmed bookings.
     *
     * This method fetches services while calculating a "discounted_price" if the service has an associated,
     * valid discount. The discount is only applied if today's date is within the discount's start and end dates.
     * Two types of discount calculations are supported:
     * - "percentage": Computes the discounted price by reducing the service price by a given percentage.
     * - "fixed": Computes the discounted price by subtracting a fixed discount amount from the service price.
     *
     * Additionally, the method ensures that the services returned have at least one booking record that is not
     * confirmed. The response includes associated category, discount, and employee details.
     *
     * On success, it returns a JSON response with the paginated results (15 services per page).
     * If an exception occurs, the error is logged and a JSON response with an error message is returned.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing paginated services data or an error message.
     *
     * @throws \Throwable If an unexpected error occurs during the service retrieval process.
     */
    public function index(): JsonResponse
    {
        try {
            //get services where booking status != confirmed
            // if has discount within valid date range then create virtual field to show discounted price. and also check discount calculation type based on type on discount field
                $today  = now()->toDateString();
                $query = Service::select([
                        'services.id',
                        'services.name',
                        'services.price',
                        'services.category_id',
                        'services.discount_id',
                        'services.description',
                    ])
                        // calculate discounted_price if discount is active
                        ->selectRaw(
                            <<<SQL
                                    CASE
                                    -- discount is valid today?
                                    WHEN discounts.start_date <= ?
                                    AND discounts.end_date   >= ?
                                        THEN
                                        CASE
                                            WHEN discounts.type = 'percentage'
                                            THEN ROUND(services.price * (1 - discounts.value/100), 2)
                                            WHEN discounts.type = 'fixed'
                                            THEN ROUND(services.price - discounts.value, 2)
                                            ELSE 0
                                        END
                                    -- discount missing or expired → show 0
                                    ELSE 0
                                    END AS discounted_price
                                SQL
                        , [$today, $today])
                    ->leftJoin('discounts', 'discounts.id', '=', 'services.discount_id')
                    ->whereExists(function($exists){
                        $exists->select(DB::raw(1))
                            ->from('bookings')
                            ->whereColumn('bookings.service_id','services.id')
                            ->where('bookings.status','!=','confirmed');
                    });

                $services = $query->with([
                        'category:id,name',
                        'discount:id,value,type,start_date,end_date',
                        'employees:id,name,description',
                    ])
                    ->paginate(15);

            return response()->json($services, 200);

        } catch (\Throwable $e) {
            Log::error('ServiceController@index error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve services.'
            ], 500);
        }
    }


    /**
     * Retrieve a service by its ID along with its associated category, discount, and employees.
     *
     * This method attempts to fetch a service record from the database using the provided ID.
     * It includes a calculation for the discounted price based on the current date and the specific
     * discount conditions (either percentage-based or fixed amount). The method also eagerly loads
     * related data such as the service category, discounts, and employees.
     *
     * @param string $id The unique identifier of the service.
     *
     * @return JsonResponse A JSON response containing the service details or an error message.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If no service matching the provided ID is found.
     * @throws \Throwable For any other errors encountered during the execution.
     */
    public function show(string $id): JsonResponse
        {
            try {
                $today = now()->toDateString();

                $service = Service::query()
                    ->select([
                        'services.id',
                        'services.name',
                        'services.price',
                        'services.category_id',
                        'services.discount_id',
                        'services.description',
                    ])
                    ->selectRaw(<<<SQL
                        CASE
                        WHEN discounts.start_date <= ?
                            AND discounts.end_date   >= ?
                        THEN
                            CASE
                            WHEN discounts.type = 'percentage'
                                THEN ROUND(services.price * (1 - discounts.value/100), 2)
                            WHEN discounts.type = 'fixed'
                                THEN ROUND(services.price - discounts.value, 2)
                            ELSE 0
                            END
                        ELSE 0
                        END AS discounted_price
                    SQL,
                    [$today, $today]
                    )
                    ->leftJoin('discounts', 'discounts.id', '=', 'services.discount_id')
                    ->with([
                        'category:id,name',
                        'discount:id,value,type,start_date,end_date',
                        'employees:id,name,description',
                    ])
                    ->where('services.id', $id)
                    ->firstOrFail();

                return response()->json($service, 200);

            } catch (ModelNotFoundException $e) {
                return response()->json([
                    'message' => 'Service not found.',
                ], 404);

            } catch (\Throwable $e) {
                Log::error('ServiceController@show error', [
                    'id'      => $id,
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'message' => 'Failed to retrieve service.',
                ], 500);
            }
    }



    /**
     * Update the specified service.
     *
     * This method handles the update of a service record based on the provided ID. It begins by validating the
     * incoming request data to ensure that all fields meet the defined rules, including the existence and
     * uniqueness checks where needed. The service is then retrieved, and its attributes are updated inside a
     * database transaction. If employee IDs are provided, their relationships are synchronized using the sync method.
     *
     * Upon successful update, the method returns a JSON response with a success message and the updated service data,
     * including its associated category, discount, and employees. If the service is not found, a 404 JSON response is
     * returned. Any unexpected errors are logged and a 500 JSON response is provided.
     *
     * @param  \Illuminate\Http\Request  $request  The HTTP request object containing the update data.
     * @param  string  $id  The unique identifier of the service to be updated.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the service with the given ID does not exist.
     * @throws \Throwable For any other errors that may occur during the update process.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'category_id'  => 'sometimes|required|exists:categories,id',
            'name'         => 'sometimes|required|string|max:255|unique:services,name,' . $id,
            'price'        => 'sometimes|required|numeric|min:0',
            'description'  => 'nullable|string',
            'discount_id'  => 'nullable|exists:discounts,id',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'integer|exists:users,id',
        ]);

        try {
            $service = Service::findOrFail($id);

            DB::transaction(function () use ($service, $data) {
                $service->update([
                    'category_id' => $data['category_id']  ?? $service->category_id,
                    'name'        => $data['name']         ?? $service->name,
                    'price'       => $data['price']        ?? $service->price,
                    'description' => $data['description'] ?? $service->description,
                    'discount_id' => array_key_exists('discount_id', $data)
                                      ? $data['discount_id']
                                      : $service->discount_id,
                ]);

                if (array_key_exists('employee_ids', $data)) {
                    $service->employees()->sync($data['employee_ids']);
                }
            });

            $service->load(['category', 'discount', 'employees']);

            return response()->json([
                'message' => 'Service updated successfully.',
                'service' => $service,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Service not found.'
            ], 404);

        } catch (\Throwable $e) {
            Log::error('ServiceController@update error', [
                'id'      => $id,
                'input'   => $data,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to update service.'
            ], 500);
        }
    }


    /**
     * Deletes the specified service.
     *
     * This method attempts to locate a service using the provided ID. If found, the service is deleted
     * from the database and a HTTP 204 (No Content) response is returned indicating the successful deletion.
     * If the service is not found, a HTTP 404 response with an appropriate error message is returned.
     * For any other unexpected errors, the error details are logged and a HTTP 500 response is provided.
     *
     * @param string $id The identifier of the service to delete.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response indicating the result of the deletion.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Thrown when the service is not found.
     * @throws \Throwable Thrown if an unexpected error occurs during the deletion process.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $service = Service::findOrFail($id);
            $service->delete();

            return response()->json([
                'message' => 'Service Deleted successfully.'
            ], 204);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Service not found.'
            ], 404);

        } catch (\Throwable $e) {
            Log::error('ServiceController@destroy error', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to delete service.'
            ], 500);
        }
    }


}
