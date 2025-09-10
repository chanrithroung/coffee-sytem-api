<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MenuManagementSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MenuCategorySeeder::class,
            MenuItemSeeder::class,
        ]);

        $this->command->info('Menu Management seeding completed!');
    }
}
