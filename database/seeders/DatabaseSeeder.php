<?php

namespace Database\Seeders;
use App\Models\Role;
use App\Models\User;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Log; 
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $roles = ['admin', 'storekeeper', 'viewer'];

        // foreach ($roles as $role) {
        //     Role::create(['name' => $role]);
        // }
        $roles = Role::all(); 


        // User::factory()->count(100)->create()->each(function ($user) use ($roles) {
        //     $user->role_id = $roles->random()->id; 
        //     $user->save();
        // });

        // Product::factory(20)->create();
        // Stock::factory(50)->create();
        $faker = Faker::create();

        for ($i = 0; $i < 50; $i++) {
           Log::create([
                'message' => $faker->sentence(),
               'level' => $faker->randomElement(['info', 'warning', 'error']),
                'user_name' => $faker->word(),
               'user_id' => $faker->numberBetween(1, 100),
               'created_at' => now(),
            ]);
           }

        echo "50 logs inserted successfully.\n";
}
} 