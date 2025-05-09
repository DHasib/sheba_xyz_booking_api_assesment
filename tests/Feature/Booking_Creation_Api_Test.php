<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Service;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Laravel\Sanctum\Sanctum;
use App\Models\Role;
use App\Models\User;
use App\Models\Category;

class Booking_Creation_Api_Test extends TestCase
{


    #[Test]
    /**
     * Test that a booking is successfully created with valid data.
     *
     * This test performs the following actions:
     * 1) Seeds a customer user with the customer role (role_id: 3) for authentication.
     * 2) Seeds a service instance (with category_id: 3) to be booked.
     * 3) Authenticates as the seeded customer using Sanctum.
     * 4) Sends a POST JSON request to the 'api/bookings' endpoint with the following data:
     *    - "bookings": An array containing the booking details (service_id and scheduled_at).
     *    - "user_id": The identifier of the user (3).
     *    - "contact_name": The name of the contact person ("John Doe").
     *    - "contact_phone": The contact phone number ("+1-555-123-4567").
     *    - "service_location": The location of the service ("123 Main St, Springfield").
     *
     * Expects the API to respond with an HTTP 201 status code, indicating successful creation.
     */
    public function booking_api_successfully_create_booking_appropriate_data(): void
    {

            /* -------------------------------------------------
            | 1) Seed a customer role + customer user
            * ------------------------------------------------- */
            // $customerRole = Role::factory()->state(['name' => 'customer'])->create();
            $customer     = User::factory()->create(['role_id' => 3]);

            /* -------------------------------------------------
            | 2) Seed one service to book
            * ------------------------------------------------- */

            $service  = Service::factory()->create(['category_id' => 3]);

            /* -------------------------------------------------
            | 3) Authenticate as that customer
            * ------------------------------------------------- */
            Sanctum::actingAs($customer, ['*']);
        $response = $this->postJson('api/bookings', [
                                    "bookings" => [
                                        [ "service_id"=> $service->id, "scheduled_at"=> "2026-05-09 15:10:00" ]
                                    ],
                                    "user_id"=> 3,
                                    "contact_name" => "John Doe",
                                    "contact_phone" => "+1-555-123-4567",
                                    "service_location" => "123 Main St, Springfield"
                                ]);
         $response->assertStatus(201);
            //409, 201, 500
    }

    /**
     * Test to verify that the booking API correctly handles scenarios with an
     * invalid service ID or user ID.
     *
     * This test sends a POST request with a JSON payload that includes:
     * - "bookings": an array containing booking details (with a service_id and a scheduled_at date),
     *   where the service_id is expected to trigger an invalid service condition.
     * - "user_id": a user ID that is also presumed to be invalid.
     * - Additional booking-related details such as contact name, contact phone, and service location.
     *
     * The expected outcome is that the API will respond with a 401 Unauthorized HTTP status,
     * indicating that the operation was not permitted due either to invalid service or user credentials.
     * Other potential HTTP status codes considered include:
     * - 409 Conflict
     * - 201 Created (for a successful operation)
     * - 500 Internal Server Error (for any unexpected failure)
     *
     * @return void
     */
    #[Test]
    public function booking_api_try_to_create_booking_with_invalid_serviceId_or_userId(): void
    {
        $response = $this->postJson('api/bookings', [
                                    "bookings" => [
                                        [ "service_id"=> 1, "scheduled_at"=> "2025-05-09 15:10:00" ]
                                    ],
                                    "user_id"=> 6,
                                    "contact_name" => "John Doe",
                                    "contact_phone" => "+1-555-123-4567",
                                    "service_location" => "123 Main St, Springfield"
                                ]);
         $response->assertStatus(401);
            //409, 201, 500
    }


    /**
     * Test to verify a successful booking creation response with the correct payload.
     *
     * This test performs the following operations:
     * - Seeds a customer user with a specific role.
     * - Seeds a category and an associated service.
     * - Authenticates the customer user.
     * - Sends a POST JSON request to create a new booking.
     *
     * The test asserts that:
     * - The response status is 201 Created.
     * - The response JSON structure contains a "message" key.
     * - The response JSON structure includes a "bookings" key which is an array of bookings.
     * - Each booking in the array includes all expected keys such as:
     *     - service_id, user_id, contact_name, contact_phone, service_location,
     *       scheduled_at, status, unique_id, updated_at, created_at, and id.
     * - Each booking also contains nested service details (id, name, price, description)
     *   and user details (id, name, email, phone, address).
     *
     * Additionally, the test explicitly checks that the "message" and "bookings"
     * keys exist in the JSON response.
     *
     * @return void
     */
    #[Test]
    public function after_booking_api_successfully_create_response_with_proper_payload(): void
    {
                    /* -------------------------------------------------
            | 1) Seed a customer role + customer user
            * ------------------------------------------------- */

            $customer     = User::factory()->create(['role_id' => 3]);

            /* -------------------------------------------------
            | 2) Seed one service to book
            * ------------------------------------------------- */

            $service  = Service::factory()->create(['category_id' => 3]);

            /* -------------------------------------------------
            | 3) Authenticate as that customer
            * ------------------------------------------------- */
            Sanctum::actingAs($customer, ['*']);
            $response = $this->postJson('api/bookings', [
                                    "bookings" => [
                                        [ "service_id"=> $service->id, "scheduled_at"=> "2026-12-12 15:10:00" ]
                                    ],
                                    "user_id"=> $customer->id,
                                    "contact_name" => "John Doe",
                                    "contact_phone" => "+1-555-123-4567",
                                    "service_location" => "123 Main St, Springfield"
                                ]);
         $response->assertCreated()
                    ->assertJsonStructure([
                        // top-level paginator keys
                        'message',
                        'bookings' => [
                            '*' => [
                               'service_id',
                                'user_id',
                                'contact_name',
                                'contact_phone',
                                'service_location',
                                'scheduled_at',
                                'status',
                                'unique_id',
                                'updated_at',
                                'created_at',
                                'id',
                                'service' => [
                                    'id',
                                    'name',
                                    'price',
                                    'description',
                                ],
                                'user' => [
                                    'id',
                                    'name',
                                    'email',
                                    'phone',
                                    'address',
                                ],
                            ],
                        ],
                    ]);
        $json = $response->json();
        $this->assertArrayHasKey('message', $json, 'Expected "message" key in response');
        $this->assertArrayHasKey('bookings', $json, 'Expected "bookings" key in response');

    }


}
