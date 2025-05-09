<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Mail\BookingStatusChanged;
use Illuminate\Support\Facades\Mail;

class BookingController extends Controller
{
    protected $now;

    /**
     * Constructor.
     *
     * Initializes the "now" property with the current timestamp formatted as "Y-m-d H:i:s".
     */
    public function __construct()
    {
        $this->now = now()->format('Y-m-d H:i:s');
    }


    /**
     * Retrieve a paginated list of bookings with associated service and user details.
     *
     * This method fetches bookings selecting specific fields, and loads the related
     * service and user records with only their id and name attributes. The bookings
     * are ordered by the scheduled date in descending order and paginated with a limit
     * of 15 per page.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the paginated bookings on success,
     *                                         or an error message with a 500 status code in case of a failure.
     *
     * @throws \Throwable Catches any throwable exceptions during processing and logs the details before
     *                    returning an error response.
     */
    public function index(): JsonResponse
    {
        try {

            $bookings = Booking::select([
                    'id',
                    'contact_name',
                    'service_id',
                    'user_id',
                    'contact_phone',
                    'service_location',
                    'status',
                    'scheduled_at',
                ])
                ->with([
                    'service:id,name',
                    'user:id,name',
                ])
                ->orderBy('scheduled_at', 'desc')
                ->paginate(15);

            return response()->json($bookings, 200);

        } catch (\Throwable $e) {
            Log::error('BookingController@index error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve bookings.',
            ], 500);
        }
    }


    /**
     * Handles the creation of new bookings based on the request data.
     *
     * This method validates the incoming request to ensure it contains an array of bookings
     * along with required user and contact information. Each booking must include a valid service
     * identifier and a scheduled date/time that is formatted correctly and set after the current time.
     *
     * The process includes:
     * 1. Validating the request data.
     * 2. Checking for any conflicting bookings by comparing the provided service_id and scheduled_at
     *    against existing records.
     * 3. Returning a conflict response (HTTP 409) if a booking slot is already taken.
     * 4. If there are no conflicts, creating the bookings within a database transaction,
     *    setting their initial status to 'pending', and eager loading related service and user information.
     * 5. Returning a successful JSON response (HTTP 201) containing the created bookings.
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing booking details.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response with a success message and the created bookings,
     *                                      or an error message if the bookings could not be processed.
     *
     * @throws \Throwable If any error occurs during the booking creation process, it will be caught
     *                    and logged, and an HTTP 500 error response will be returned.
     */
    public function store(Request $request): JsonResponse
    {
        $now = now()->format('Y-m-d H:i:s');
        $data = $request->validate([
                'bookings'                => 'required|array|min:1',
                'bookings.*.service_id'   => 'required|integer|exists:services,id',
                'bookings.*.scheduled_at' => ['required', 'date_format:Y-m-d H:i:s', 'after:' . $this->now],
                'user_id'                 => 'required|exists:users,id',
                'contact_name'            => 'required|string|max:255',
                'contact_phone'           => 'required|string|max:20',
                'service_location'        => 'required|string|max:255',
            ]);

            $items =  $data['bookings'];
        try {

            // 1) Check for any conflicts
                $existing = Booking::where(function($q) use ($items) {
                    foreach ($items as $item) {
                        $q->orWhere(fn($q2) => $q2
                            ->where('service_id',   $item['service_id'])
                            ->where('scheduled_at', $item['scheduled_at'])
                        );
                    }
                })
                ->with('service:id,name')
                ->get();

                // If any bookings exist, gather their names and times
                if ($existing->isNotEmpty()) {
                    $descriptions = $existing->map(fn($b) =>
                        "{$b->service->name} at {$b->scheduled_at}"
                    )->all();

                    return response()->json([
                        'message'   => 'The following slot(s) are already booked: '
                                    . implode(', ', $descriptions)
                                    . '. Please pick a different time schedule.',
                    ], 409);
                }

             // 2) Create bookings in a transaction
                $created = DB::transaction(function () use ($items, $data) {
                    return collect($items)
                        ->map(function ($item) use ($data) {
                            return Booking::create([
                                'service_id'      => $item['service_id'],
                                'user_id'         => $data['user_id'],
                                'contact_name'    => $data['contact_name'],
                                'contact_phone'   => $data['contact_phone'],
                                'service_location'=> $data['service_location'],
                                'scheduled_at'    => $item['scheduled_at'],
                                'status'          => 'pending',
                                'unique_id'      => uniqid(),
                            ])->load(['service:id,name', 'user:id,name']);
                        })
                        ->all();
                });

            return response()->json([
                'message'  => 'Bookings created successfully.',
                'bookings' => $created,
            ], 201);

        } catch (\Throwable $e) {
            Log::error('BookingController@store error', [
                'input'   => $data,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to create bookings. ' . $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Display the specified booking.
     *
     * This method retrieves a booking record by its identifier, including associated service and user details.
     * It performs an authorization check to ensure that the current authenticated user has permissions to view
     * the booking. Specifically, if the user is an admin or if the user is a customer who owns the booking, the booking
     * information is returned. Otherwise, the method responds with an authorization error.
     *
     * If the booking is not found, a JSON error message with a 404 status code is returned. In case of other errors,
     * the exception is logged and a JSON error message with a 500 status code is returned.
     *
     * @param string $id The identifier of the booking.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response containing the booking details or an error message.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the booking with the given ID is not found.
     * @throws \Throwable For any other exceptions that occur during the retrieval process.
     */
    public function show(string $id): JsonResponse
    {
        try {

            $user = Auth::user();
            $booking = Booking::with([
                    'service:id,name,description,price',
                    'user:id,name,email,phone,address',
                ])
                ->findOrFail($id);

            if (! ($user->role->name === 'admin'|| ($user->role->name === 'customer' && $booking->user_id === $user->id) ))  {
                return response()->json([
                    'message' => 'You do not have permission to access this booking.'
                ], 401);
            }

            return response()->json($booking, 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Booking not found.',
            ], 404);

        } catch (\Throwable $e) {
            Log::error('BookingController@show error', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve booking. ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Update a booking record.
     *
     * This method updates an existing booking using data provided in the request.
     * It validates the input data (ensuring proper format and required fields) and only allows updates
     * when the booking status is "pending". The update is performed within a database transaction,
     * and upon success, related service and user information are loaded for the response.
     *
     * @param Request $request The HTTP request instance containing update data.
     * @param string  $id      The unique identifier of the booking to be updated.
     *
     * @return JsonResponse Returns a JSON response containing a success message and the updated booking,
     *                      or an error message and status code if the update fails.
     *
     * @throws ModelNotFoundException Thrown when the booking with the given ID is not found.
     * @throws \Throwable             Thrown if any error occurs during the booking update process.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'contact_name'    => 'sometimes|required|string|max:255',
            'contact_phone'   => 'sometimes|required|string|max:20',
            'service_location'=> 'sometimes|required|string|max:255',
            'scheduled_at'    => 'sometimes|required|date_format:Y-m-d H:i:s|after:' . $this->now,
        ]);

        try {
            $booking = Booking::findOrFail($id);

            // 3) Only allow updates when status is still 'pending'
            if ($booking->status !== 'pending') {
                return response()->json([
                    'message' => 'Cannot update booking once it is ' . $booking->status . '.',
                ], 403);
            }

            DB::transaction(function () use ($booking, $data) {
                $booking->update($data);
            });

            $booking->load(['service:id,name','user:id,name']);

            return response()->json([
                'message' => 'Booking updated successfully.',
                'booking' => $booking,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Booking not found.',
            ], 404);

        } catch (\Throwable $e) {
            Log::error('BookingController@update error', [
                'id'      => $id,
                'input'   => $data,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to update booking.',
            ], 500);
        }
    }


    /**
     * Delete a booking resource.
     *
     * This method locates a booking by its unique identifier and attempts to delete it.
     * Deletion is only permitted if the current status of the booking is 'pending'.
     * If the booking's status is not 'pending', it returns a 403 response.
     * If the booking resource is not found, it returns a 404 response.
     * Any other errors during deletion return a 500 response.
     *
     * @param string $id The unique identifier of the booking to be deleted.
     *
     * @return JsonResponse A response indicating the result of the deletion operation.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $booking = Booking::findOrFail($id);
            // 3) Only allow updates when status is still 'pending'
            if ($booking->status !== 'pending') {
                return response()->json([
                    'message' => 'Cannot delete booking once it is ' . $booking->status . '.',
                ], 403);
            }
            $booking->delete();

               return response()->json([
                'message' => 'Booking deleted successfully.',
            ], 204);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Booking not found.',
            ], 404);

        } catch (\Throwable $e) {
            Log::error('BookingController@destroy error', [
                'id'      => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to delete booking.',
            ], 500);
        }
    }



    /**
     * Retrieve booking status information by unique booking identifier.
     *
     * This method fetches bookings filtered by the provided unique identifier. It selects
     * key fields including id, service_id, status, and scheduled date, while also eager loading
     * the associated service information (id and name only). The bookings are sorted with the
     * most recent scheduled date first.
     *
     * @param mixed $id The unique booking identifier.
     * @return \Illuminate\Http\JsonResponse JSON response containing the booking data or an error message in case of failure.
     */
    public function getBookingStatusByUniqueId($id)
    {
        try {
            $bookings = Booking::where('unique_id', $id)
                                ->select([
                                    'id',
                                    'service_id',
                                    'status',
                                    'scheduled_at',
                                ])
                                ->with(['service:id,name'])
                                ->orderBy('scheduled_at', 'desc')
                                ->get();

            return response()->json($bookings, 200);

        } catch (\Throwable $e) {
            Log::error('BookingController@getBookingStatusByUniqueId error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve bookings.',
            ], 500);
        }
    }


    /**
     * Update the status of a booking.
     *
     * This method validates the incoming request to ensure that a valid 'status' and 'booking_id'
     * are provided. The 'status' must be one of the allowed values: confirmed, cancelled, or completed.
     * It then attempts to locate the booking by its ID. If found, it updates the booking's status and
     * persists the change to the database. On success, a JSON response with the updated booking details
     * is returned.
     *
     * In case the booking does not exist, a ModelNotFoundException is caught and a 404 response is returned.
     * Any other exceptions are caught, logged, and a 500 error response is returned.
     *
     * @param Request $request The HTTP request instance containing:
     *                         - status: The new status for the booking (string, required, allowed values: confirmed, cancelled, completed).
     *                         - booking_id: The ID of the booking to be updated (integer, required, must exist in bookings).
     *
     * @return JsonResponse JSON response containing a message and the updated booking details on success,
     *                      or an error message on failure.
     *
     * @throws ModelNotFoundException If the booking with the specified ID cannot be found.
     * @throws \Throwable             For any other errors that occur during the update process.
     */
    public function updateBookingStatus(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:confirmed,cancelled,completed',
            'booking_id' => 'required|integer|exists:bookings,id',
        ]);

        try {
            $id = $request->input('booking_id');
            $booking = Booking::findOrFail($id);

            $oldStatus = $booking->status;
            $booking->status = $request->input('status');

            DB::transaction(function () use ($booking) {
                $booking->save();
            });

            // Only notify on confirmed or cancelled (and only if it actually changed)
            if (in_array($booking->status, ['confirmed','cancelled','completed'])
                && $booking->status !== $oldStatus)
            {
                // eager-load relations for the email
                // dd($booking->user->email);
                $booking->load(['user','service']);
                Mail::to($booking->user->email)
                    ->send(new BookingStatusChanged($booking));
            }

            return response()->json([
                'message' => 'Booking status updated successfully.',
                'booking' => $booking,
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Booking not found.',
            ], 404);

        } catch (\Throwable $e) {
            Log::error('BookingController@updateBookingStatus error', [
                'id'      => $id,
                'input'   => $request->all(),
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => "Failed to update booking status. {$e->getMessage()}",
            ], 500);
        }
    }

}


