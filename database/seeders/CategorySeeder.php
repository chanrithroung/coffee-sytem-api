<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Hot Coffee',
                'description' => 'Freshly brewed hot coffee beverages',
                'color' => '#8B4513',
                'sort_order' => 1,
                'metadata' => ['temperature' => 'hot', 'caffeine' => 'high']
            ],
            [
                'name' => 'Cold Coffee',
                'description' => 'Refreshing iced coffee drinks',
                'color' => '#4682B4',
                'sort_order' => 2,
                'metadata' => ['temperature' => 'cold', 'caffeine' => 'high']
            ],
            [
                'name' => 'Tea & Herbal',
                'description' => 'Premium teas and herbal infusions',
                'color' => '#228B22',
                'sort_order' => 3,
                'metadata' => ['temperature' => 'both', 'caffeine' => 'medium']
            ],
            [
                'name' => 'Smoothies',
                'description' => 'Fresh fruit smoothies and blends',
                'color' => '#FF69B4',
                'sort_order' => 4,
                'metadata' => ['temperature' => 'cold', 'caffeine' => 'none']
            ],
            [
                'name' => 'Pastries',
                'description' => 'Freshly baked pastries and desserts',
                'color' => '#DEB887',
                'sort_order' => 5,
                'metadata' => ['type' => 'food', 'category' => 'bakery']
            ],
            [
                'name' => 'Sandwiches',
                'description' => 'Gourmet sandwiches and wraps',
                'color' => '#CD853F',
                'sort_order' => 6,
                'metadata' => ['type' => 'food', 'category' => 'main']
            ],
            [
                'name' => 'Snacks',
                'description' => 'Light snacks and finger foods',
                'color' => '#DAA520',
                'sort_order' => 7,
                'metadata' => ['type' => 'food', 'category' => 'light']
            ],
            [
                'name' => 'Specialty Drinks',
                'description' => 'Unique signature beverages',
                'color' => '#9370DB',
                'sort_order' => 8,
                'metadata' => ['type' => 'signature', 'premium' => true]
            ],
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'description' => $category['description'],
                'color' => $category['color'],
                'sort_order' => $category['sort_order'],
                'is_active' => true,
                'metadata' => $category['metadata'],
            ]);
        }
    }
}
