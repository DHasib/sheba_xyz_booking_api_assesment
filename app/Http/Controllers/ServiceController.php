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


}
