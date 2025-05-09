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
     * Store a newly created service in storage.
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



}
