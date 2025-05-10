<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Role;    

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{

    protected $model = \App\Models\User::class;
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('P@ssword'),
            'remember_token' => Str::random(10),
            'address'    => $this->faker->address(),
            'phone'      => $this->faker->phoneNumber(),
            'description' => $this->faker->sentence(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
    }

      /**
     * Admin state – ensures the user gets the “admin” role.
     */
    public function admin(): self
    {
        return $this->state(function () {
            // fetch or create the admin role once
            $adminRole = Role::firstOrCreate(['name' => 'admin']);

            return [
                'name' => "admin",
                'email'   => 'admin@example.com',
                'email_verified_at' => now(),
                'password' => static::$password ??= Hash::make('P@ssword'),
                'remember_token' => Str::random(10),
                'address'    => "123 Admin St",
                'phone'      => "123-456-7890",
                'description' => "Admin user",
                'created_at'  => now(),
                'updated_at'  => now(),
                'role_id' => $adminRole->id,
            ];
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
