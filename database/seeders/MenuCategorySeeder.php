<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuCategory;

class MenuCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Hot Coffee',
                'description' => 'Freshly brewed hot coffee beverages',
                'icon' => '☕',
                'color' => '#8B4513',
                'sort_order' => 1,
                'is_active' => true,
                'is_visible' => true,
                // 'available_days' => [0, 1, 2, 3, 4, 5, 6], // Will use model default
            ],
            [
                'name' => 'Iced Coffee',
                'description' => 'Refreshing cold coffee beverages',
                'icon' => '🧊',
                'color' => '#4A90E2',
                'sort_order' => 2,
                'is_active' => true,
                'is_visible' => true,
                // 'available_days' => [0, 1, 2, 3, 4, 5, 6], // Will use model default
            ],
            [
                'name' => 'Specialty Drinks',
                'description' => 'Unique and seasonal beverages',
                'icon' => '⭐',
                'color' => '#F39C12',
                'sort_order' => 3,
                'is_active' => true,
                'is_visible' => true,
                // 'available_days' => [0, 1, 2, 3, 4, 5, 6], // Will use model default
            ],
            [
                'name' => 'Pastries & Desserts',
                'description' => 'Fresh baked goods and sweet treats',
                'icon' => '🥐',
                'color' => '#E67E22',
                'sort_order' => 4,
                'is_active' => true,
                'is_visible' => true,
                // 'available_days' => [0, 1, 2, 3, 4, 5, 6], // Will use model default
            ],
            [
                'name' => 'Breakfast',
                'description' => 'Morning favorites and light meals',
                'icon' => '🍳',
                'color' => '#27AE60',
                'sort_order' => 5,
                'is_active' => true,
                'is_visible' => true,
                'available_from' => '06:00:00',
                'available_to' => '11:30:00',
                // 'available_days' => [0, 1, 2, 3, 4, 5, 6], // Will use model default
            ],
        ];

        foreach ($categories as $category) {
            MenuCategory::create($category);
        }
    }
}
