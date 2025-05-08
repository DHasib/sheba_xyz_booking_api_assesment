<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    protected $model = \App\Models\Service::class;


    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id'    => \App\Models\Category::factory(),
            'name'           => $this->faker->unique()->words(2, true),
            'price'          => $this->faker->randomFloat(2, 50, 500),
            'description'    => $this->faker->sentence(),
            'discount_id'    => null, // set to null by default
            'created_at'     => now(),
            'updated_at'     => now(),

            // assign discount_id optionally in seeder
        ];
    }
}
