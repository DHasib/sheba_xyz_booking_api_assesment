<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Service;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;        // ← add
use App\Models\Role;           // ← add
use Laravel\Sanctum\Sanctum;   // ← add

class Booking_Status_Retrieval_Api_Test extends TestCase
{
    use WithFaker;      // ← enable $this->faker

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFaker();      // initialise faker for non-Pest tests
    }

    #[Test]
    /**
     * Test the API endpoint for retrieving a booking's status by its unique identifier.
     *
     * This function carries out the following steps:
     * 1. Creates a customer user with a designated role (role_id = 3).
     * 2. Seeds a service record with a specific category (category_id = 3) to be booked.
     * 3. Authenticates as the created customer using Laravel Sanctum.
     * 4. Submits a POST request to create a booking with necessary details, including:
     *    - Service information,
     *    - Scheduled time,
     *    - User contact and location details.
     * 5. Asserts that the booking creation returns an HTTP 201 status code.
     * 6. Extracts the unique booking identifier from the response.
     * 7. Sends a GET request to the booking status endpoint using the extracted unique ID.
     * 8. Asserts that the status retrieval returns a successful HTTP 200 status code.
     *
     * @return void
     */
    public function bookings_status_api_return_booking_status_by_unique_id(): void
    {
        $customer     = User::factory()->create(['role_id' => 3]);

            /* -------------------------------------------------
            | 2) Seed one service to book
            * ------------------------------------------------- */

            $service  = Service::factory()->create(['category_id' => 3]);

            /* -------------------------------------------------
            | 3) Authenticate as that customer
            * ------------------------------------------------- */
            Sanctum::actingAs($customer, ['*']);

        $responseCreate = $this->postJson('api/bookings', [
                                    "bookings" => [
                                        [
                                            "service_id"=> $service->id,
                                            "scheduled_at"=> $this->faker->dateTimeBetween('now','+2 weeks')->format('Y-m-d H:i:s'),
                                        ]
                                    ],
                                    "user_id"=> 3,
                                    "contact_name" => "John Doe",
                                    "contact_phone" =>  $this->faker->unique()->numerify('+1-555-###-####'),
                                    "service_location" => "123 Main St, Springfield"
                                ]);

        $responseCreate->assertCreated();   // 201

        $json      = $responseCreate->json();
        $uniqueId  = $json['bookings'][0]['unique_id'];

        $response = $this->getJson('/api/bookings/status/' . $uniqueId);
        // dd($response->json(), $response->assertOk());
        $response->assertOk();
    }

}
