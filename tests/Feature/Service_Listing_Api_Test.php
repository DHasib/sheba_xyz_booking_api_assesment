<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Service;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class Service_Listing_Api_Test extends TestCase
{


    #[Test]
    /**
     * Test that the '/api/services' endpoint properly returns a paginated list of services.
     *
     * This method tests that:
     * - The response status is OK.
     * - The JSON response contains all necessary pagination keys: 'current_page', 'first_page_url', 'from',
     *   'last_page', 'last_page_url', 'links', 'next_page_url', 'path', 'per_page', 'prev_page_url', 'to', and 'total'.
     * - The 'data' key in the JSON response is an array of services. Each service object must have:
     *   - Basic service details: 'id', 'name', 'price', 'discounted_price', and 'description'.
     *   - A 'category' containing both 'id' and 'name'.
     *   - An optional 'discount'.
     *   - An 'employees' array.
     * - Key assertions ensure the response structure adheres to the expected schema.
     *
     * @return void
     */
    public function it_returns_services_list_with_proper_pagination(): void
    {
        $response = $this->getJson('/api/services');
        $response->assertOk()
                    ->assertJsonStructure([
                        // top-level paginator keys
                        'current_page',
                        'first_page_url',
                        'from',
                        'last_page',
                        'last_page_url',
                        'links',
                        'next_page_url',
                        'path',
                        'per_page',
                        'prev_page_url',
                        'to',
                        'total',

                        'data' => [
                            '*' => [
                                'id',
                                'name',
                                'price',
                                'discounted_price',
                                'description',
                                'category' => ['id','name'],
                                'discount',   // may be null
                                'employees',  // array
                            ],
                        ],
                    ]);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json, 'Expected "data" key in response');
        $this->assertArrayHasKey('per_page', $json, 'Expected "per_page" key in response');
        $this->assertArrayHasKey('current_page', $json, 'Expected "current_page" key in response');
        $this->assertArrayHasKey('total', $json, 'Expected "total" key in response');
        $this->assertArrayHasKey('last_page', $json, 'Expected "last_page" key in response');
        $this->assertArrayHasKey('first_page_url', $json, 'Expected "first_page_url" key in response');
        $this->assertArrayHasKey('last_page_url', $json, 'Expected "last_page_url" key in response');
        $this->assertArrayHasKey('next_page_url', $json, 'Expected "next_page_url" key in response');
        $this->assertArrayHasKey('prev_page_url', $json, 'Expected "prev_page_url" key in response');
        $this->assertArrayHasKey('from', $json, 'Expected "from" key in response');

    }


    #[Test]
    /**
     * Test to ensure that the '/api/services' endpoint returns a non-empty list of services.
     *
     * This test sends a GET request to the API's '/api/services' endpoint, retrieves the response data,
     * and converts it into a collection of services. It then asserts that the collection is not empty,
     * ensuring that at least one service has been returned. If the collection is empty, the test will fail.
     *
     * @return void
     */
    public function it_returns_services_list_with_NotNull(): void
    {
        $response = $this->getJson('/api/services');
        $services = collect($response->json('data'));
        $this->assertTrue($services->isNotEmpty(), 'Expected at least one service in response');

    }


    #[Test]
    /**
     * Test to ensure that the number of service records returned on the first page
     * does not exceed the 'per_page' limit defined in the API response.
     *
     * This method performs a GET request to the '/api/services' endpoint, retrieves the JSON response,
     * and compares the count of records in the 'data' array with the 'per_page' value to confirm that the
     * returned record count is within the defined pagination limit.
     *
     * Assertion:
     * - The count of records in 'data' should be less than or equal to the 'per_page' value.
     */
    public function it_returns_services_list_with_per_page(): void
    {
         $response = $this->getJson('/api/services');

           $json = $response->json();
               // per_page consistency check

            $this->assertLessThanOrEqual(
                (int) $json['per_page'],
                count($json['data']),
                'First page record count exceeds per_page value'
            );

    }

    #[Test]
    /**
     * Test to verify the services list API returns a proper status code.
     *
     * This test sets a fixed current time using Carbon::setTestNow with the date '2025-05-10 00:00:00', 
     * then performs a GET request to '/api/services' expecting a JSON response.
     * The main assertion ensures that the API returns an HTTP 200 status, confirming a successful response.
     *
     * @return void
     */
    public function it_returns_services_list_with_proper_status_code(): void
    {

        Carbon::setTestNow('2025-05-10 00:00:00');
        $response = $this->getJson('/api/services');

        // 1) Basic JSON structure
        $response->assertStatus(200);

    }


    #[Test]
    /**
     * Test that the services endpoint returns a list with correctly calculated discounted prices.
     *
     * This test sets a fixed current date and retrieves the list of services from the API.
     * For the first service in the list, it calculates the expected discounted price by:
     *   - Checking if a discount exists and if the current date falls between the discount's start and end dates.
     *   - Applying a percentage-based calculation if the discount type is 'percentage', or a fixed discount subtraction otherwise.
     * The calculated expected price is then compared to the discounted price provided in the API response.
     *
     * @return void
     */
    public function it_returns_services_list_with_proper_discounted_prices(): void
    {

        Carbon::setTestNow('2025-05-10 00:00:00');
        $response = $this->getJson('/api/services');

        $services = collect($response->json('data'));

        $first = $services->first();
        $service = Service::with('discount')->findOrFail($first['id']);


        $expected = 0.0;
        if ($service->discount
            && Carbon::now()->between($service->discount->start_date, $service->discount->end_date)) {
            if ($service->discount->type === 'percentage') {
                $expected = round($service->price * (1 - $service->discount->value / 100), 2);
            } else {
                $expected = round($service->price - $service->discount->value, 2);
            }
        }

        // Assert the returned value matches
        $this->assertEquals(
            $expected,
            (float) $first['discounted_price'],
            'The discounted_price for service ID ' . $service->id . ' did not match expected'
        );
    }
}
