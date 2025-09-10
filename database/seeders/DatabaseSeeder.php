<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        \App\Models\User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@coffee.com',
            'password' => bcrypt('admin12345'),
            'role' => 'admin',
            'is_active' => true,
        ]);



        // Create sale user
        \App\Models\User::factory()->create([
            'name' => 'Sale User',
            'email' => 'sale@coffee.com',
            'password' => bcrypt('sale12345'),
            'role' => 'sale',
            'is_active' => true,
        ]);

        // Seed business data
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            TableSeeder::class,
            SettingsSeeder::class, 
        ]);
    }
}
