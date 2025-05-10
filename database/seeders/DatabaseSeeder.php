<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\{Category, Discount, Role, User, Service, Booking};
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
       // 1) Core lookups
       Category::factory(5)->create();
       Discount::factory(3)->create();
       Role::factory()->state(['name'=>'admin'])->create();
       Role::factory()->state(['name'=>'employee'])->create();
       Role::factory()->state(['name'=>'customer'])->create();

       $adminRole    = Role::where('name','admin')->first();
       $empRole      = Role::where('name','employee')->first();
       $custRole     = Role::where('name','customer')->first();

       // 2) Users
       User::factory(2)->create(['role_id' => $adminRole->id]);
       User::factory(8)->create(['role_id' => $empRole->id]);
       User::factory(20)->create(['role_id'=> $custRole->id]);

       // create a single admin user with known credentials
       User::factory()->admin()->create();


       // 3) Services & attach employees + optional discount
       Service::factory(10)
           ->create()
           ->each(function($service) use($empRole){
               // optionally assign a discount
               if (rand(0,1)) {
                   $service->discount_id = Discount::inRandomOrder()->first()->id;
                   $service->save();
               }
               // attach 1–3 random employees
               $employees = User::where('role_id', $empRole->id)
                                 ->inRandomOrder()
                                 ->take(rand(1,3))
                                 ->pluck('id');
               $service->employees()->attach($employees);
           });

       // 4) Bookings
       Booking::factory(30)->create();
    }
}
