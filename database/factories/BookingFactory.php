<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Service;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    protected $model = \App\Models\Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
         return [
            // pick an existing service:
            'service_id'       => Service::inRandomOrder()->first()->id,

            // pick an existing user (e.g. a “customer” you seeded earlier):
            'user_id'          => User::inRandomOrder()->first()->id,
            'contact_name'    => $this->faker->name(),
            'contact_phone'   => $this->faker->phoneNumber(),
            'service_location'=> $this->faker->address(),
            'status'          => $this->faker->randomElement(['pending','confirmed','cancelled']),
            'scheduled_at'    => $this->faker->dateTimeBetween('now','+2 weeks'),
            'created_at'      => now(),
            'updated_at'      => now(),
        ];
    }
}
