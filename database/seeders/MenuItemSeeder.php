<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuItem;
use App\Models\MenuCategory;
use App\Models\Product;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing categories and products
        $categories = MenuCategory::all();
        $products = Product::all();

        if ($categories->isEmpty()) {
            $this->command->warn('No menu categories found. Please run MenuCategorySeeder first.');
            return;
        }

        $menuItems = [
            // Hot Coffee
            [
                'category_id' => $categories->where('name', 'Hot Coffee')->first()->id,
                'name' => 'Espresso',
                'description' => 'Single shot of pure coffee',
                'price' => 2.50,
                'preparation_time' => 2,
                'tags' => ['coffee', 'hot', 'espresso'],
                'allergens' => [],
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'category_id' => $categories->where('name', 'Hot Coffee')->first()->id,
                'name' => 'Cappuccino',
                'description' => 'Espresso with steamed milk and foam',
                'price' => 4.50,
                'preparation_time' => 4,
                'tags' => ['coffee', 'hot', 'milk'],
                'allergens' => ['milk'],
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'category_id' => $categories->where('name', 'Hot Coffee')->first()->id,
                'name' => 'Latte',
                'description' => 'Espresso with steamed milk',
                'price' => 4.00,
                'preparation_time' => 4,
                'tags' => ['coffee', 'hot', 'milk'],
                'allergens' => ['milk'],
                'sort_order' => 3,
            ],

            // Iced Coffee
            [
                'category_id' => $categories->where('name', 'Iced Coffee')->first()->id,
                'name' => 'Iced Americano',
                'description' => 'Espresso with cold water over ice',
                'price' => 3.50,
                'preparation_time' => 3,
                'tags' => ['coffee', 'cold', 'iced'],
                'allergens' => [],
                'sort_order' => 1,
            ],
            [
                'category_id' => $categories->where('name', 'Iced Coffee')->first()->id,
                'name' => 'Iced Latte',
                'description' => 'Espresso with cold milk over ice',
                'price' => 4.50,
                'preparation_time' => 4,
                'tags' => ['coffee', 'cold', 'milk'],
                'allergens' => ['milk'],
                'sort_order' => 2,
            ],

            // Specialty Drinks
            [
                'category_id' => $categories->where('name', 'Specialty Drinks')->first()->id,
                'name' => 'Caramel Macchiato',
                'description' => 'Espresso with vanilla-flavored syrup and caramel drizzle',
                'price' => 5.50,
                'preparation_time' => 5,
                'tags' => ['coffee', 'specialty', 'sweet'],
                'allergens' => ['milk'],
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'category_id' => $categories->where('name', 'Specialty Drinks')->first()->id,
                'name' => 'Mocha',
                'description' => 'Espresso with chocolate syrup and steamed milk',
                'price' => 5.00,
                'preparation_time' => 5,
                'tags' => ['coffee', 'chocolate', 'milk'],
                'allergens' => ['milk'],
                'sort_order' => 2,
            ],

            // Pastries & Desserts
            [
                'category_id' => $categories->where('name', 'Pastries & Desserts')->first()->id,
                'name' => 'Croissant',
                'description' => 'Buttery, flaky French pastry',
                'price' => 3.50,
                'preparation_time' => 1,
                'tags' => ['pastry', 'breakfast', 'butter'],
                'allergens' => ['gluten', 'milk'],
                'sort_order' => 1,
            ],
            [
                'category_id' => $categories->where('name', 'Pastries & Desserts')->first()->id,
                'name' => 'Chocolate Cake',
                'description' => 'Rich chocolate layer cake',
                'price' => 6.00,
                'preparation_time' => 1,
                'tags' => ['dessert', 'chocolate', 'cake'],
                'allergens' => ['gluten', 'milk', 'eggs'],
                'sort_order' => 2,
            ],

            // Breakfast
            [
                'category_id' => $categories->where('name', 'Breakfast')->first()->id,
                'name' => 'Avocado Toast',
                'description' => 'Sourdough bread with mashed avocado and sea salt',
                'price' => 8.50,
                'preparation_time' => 8,
                'tags' => ['breakfast', 'healthy', 'vegetarian'],
                'allergens' => ['gluten'],
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'category_id' => $categories->where('name', 'Breakfast')->first()->id,
                'name' => 'Eggs Benedict',
                'description' => 'Poached eggs on English muffin with hollandaise sauce',
                'price' => 12.00,
                'preparation_time' => 12,
                'tags' => ['breakfast', 'eggs', 'sauce'],
                'allergens' => ['gluten', 'milk', 'eggs'],
                'sort_order' => 2,
            ],
        ];

        foreach ($menuItems as $item) {
            // Try to find a matching product by name
            $product = $products->first(function ($p) use ($item) {
                return stripos($p->name, $item['name']) !== false;
            });

            if ($product) {
                $item['product_id'] = $product->id;
            }

            MenuItem::create($item);
        }

        $this->command->info('Menu items seeded successfully!');
    }
}
