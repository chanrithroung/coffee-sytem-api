<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all()->keyBy('name');

        $products = [
            // Hot Coffee
            [
                'category' => 'Hot Coffee',
                'name' => 'Espresso',
                'description' => 'Rich, concentrated coffee shot',
                'price' => 2.50,
                'cost' => 0.80,
                'stock_quantity' => 100,
                'preparation_time' => 3,
                'variants' => ['size' => ['single', 'double']]
            ],
            [
                'category' => 'Hot Coffee',
                'name' => 'Americano',
                'description' => 'Espresso with hot water',
                'price' => 3.00,
                'cost' => 0.90,
                'stock_quantity' => 100,
                'preparation_time' => 4,
                'variants' => ['size' => ['small', 'medium', 'large']]
            ],
            [
                'category' => 'Hot Coffee',
                'name' => 'Cappuccino',
                'description' => 'Espresso with steamed milk and foam',
                'price' => 4.50,
                'cost' => 1.20,
                'stock_quantity' => 100,
                'preparation_time' => 6,
                'variants' => ['size' => ['small', 'medium', 'large'], 'milk' => ['whole', 'skim', 'almond', 'oat']]
            ],
            [
                'category' => 'Hot Coffee',
                'name' => 'Latte',
                'description' => 'Espresso with steamed milk',
                'price' => 4.25,
                'cost' => 1.15,
                'stock_quantity' => 100,
                'preparation_time' => 5,
                'variants' => ['size' => ['small', 'medium', 'large'], 'milk' => ['whole', 'skim', 'almond', 'oat']]
            ],
            // Cold Coffee
            [
                'category' => 'Cold Coffee',
                'name' => 'Iced Coffee',
                'description' => 'Chilled brewed coffee over ice',
                'price' => 3.50,
                'cost' => 1.00,
                'stock_quantity' => 100,
                'preparation_time' => 3,
                'variants' => ['size' => ['small', 'medium', 'large']]
            ],
            [
                'category' => 'Cold Coffee',
                'name' => 'Cold Brew',
                'description' => 'Smooth, cold-steeped coffee',
                'price' => 4.00,
                'cost' => 1.30,
                'stock_quantity' => 50,
                'preparation_time' => 2,
                'variants' => ['size' => ['small', 'medium', 'large']]
            ],
            [
                'category' => 'Cold Coffee',
                'name' => 'Frappuccino',
                'description' => 'Blended coffee with ice and milk',
                'price' => 5.50,
                'cost' => 1.80,
                'stock_quantity' => 75,
                'preparation_time' => 8,
                'variants' => ['flavor' => ['vanilla', 'caramel', 'mocha'], 'size' => ['medium', 'large']]
            ],
            // Tea & Herbal
            [
                'category' => 'Tea & Herbal',
                'name' => 'Earl Grey Tea',
                'description' => 'Classic black tea with bergamot',
                'price' => 2.75,
                'cost' => 0.60,
                'stock_quantity' => 80,
                'preparation_time' => 4,
                'variants' => ['temperature' => ['hot', 'iced']]
            ],
            [
                'category' => 'Tea & Herbal',
                'name' => 'Green Tea',
                'description' => 'Antioxidant-rich green tea',
                'price' => 2.50,
                'cost' => 0.55,
                'stock_quantity' => 90,
                'preparation_time' => 3,
                'variants' => ['temperature' => ['hot', 'iced']]
            ],
            [
                'category' => 'Tea & Herbal',
                'name' => 'Chamomile Tea',
                'description' => 'Soothing herbal chamomile',
                'price' => 2.25,
                'cost' => 0.50,
                'stock_quantity' => 60,
                'preparation_time' => 5,
                'variants' => ['temperature' => ['hot']]
            ],
            // Smoothies
            [
                'category' => 'Smoothies',
                'name' => 'Berry Blast',
                'description' => 'Mixed berries with yogurt',
                'price' => 6.00,
                'cost' => 2.20,
                'stock_quantity' => 40,
                'preparation_time' => 5,
                'variants' => ['size' => ['medium', 'large']]
            ],
            [
                'category' => 'Smoothies',
                'name' => 'Tropical Mango',
                'description' => 'Mango, pineapple, and coconut',
                'price' => 6.50,
                'cost' => 2.40,
                'stock_quantity' => 35,
                'preparation_time' => 6,
                'variants' => ['size' => ['medium', 'large']]
            ],
            // Pastries
            [
                'category' => 'Pastries',
                'name' => 'Croissant',
                'description' => 'Buttery, flaky French pastry',
                'price' => 3.25,
                'cost' => 1.00,
                'stock_quantity' => 25,
                'preparation_time' => 2,
                'variants' => ['type' => ['plain', 'chocolate', 'almond']]
            ],
            [
                'category' => 'Pastries',
                'name' => 'Blueberry Muffin',
                'description' => 'Fresh blueberry muffin',
                'price' => 2.75,
                'cost' => 0.85,
                'stock_quantity' => 20,
                'preparation_time' => 1,
                'variants' => []
            ],
            [
                'category' => 'Pastries',
                'name' => 'Chocolate Chip Cookie',
                'description' => 'Homemade chocolate chip cookie',
                'price' => 2.00,
                'cost' => 0.60,
                'stock_quantity' => 30,
                'preparation_time' => 1,
                'variants' => []
            ],
            // Sandwiches
            [
                'category' => 'Sandwiches',
                'name' => 'Turkey Club',
                'description' => 'Turkey, bacon, lettuce, tomato',
                'price' => 8.50,
                'cost' => 3.20,
                'stock_quantity' => 15,
                'preparation_time' => 8,
                'variants' => ['bread' => ['white', 'wheat', 'sourdough']]
            ],
            [
                'category' => 'Sandwiches',
                'name' => 'Veggie Wrap',
                'description' => 'Fresh vegetables in a tortilla wrap',
                'price' => 7.25,
                'cost' => 2.80,
                'stock_quantity' => 12,
                'preparation_time' => 6,
                'variants' => ['wrap' => ['flour', 'wheat', 'spinach']]
            ],
            // Snacks
            [
                'category' => 'Snacks',
                'name' => 'Mixed Nuts',
                'description' => 'Assorted roasted nuts',
                'price' => 4.50,
                'cost' => 1.80,
                'stock_quantity' => 25,
                'preparation_time' => 1,
                'variants' => []
            ],
            [
                'category' => 'Snacks',
                'name' => 'Granola Bar',
                'description' => 'Healthy oats and honey bar',
                'price' => 3.00,
                'cost' => 1.20,
                'stock_quantity' => 40,
                'preparation_time' => 1,
                'variants' => ['flavor' => ['original', 'chocolate chip', 'cranberry']]
            ],
            // Specialty Drinks
            [
                'category' => 'Specialty Drinks',
                'name' => 'Signature Mocha',
                'description' => 'Our special house mocha blend',
                'price' => 6.75,
                'cost' => 2.10,
                'stock_quantity' => 50,
                'preparation_time' => 7,
                'variants' => ['temperature' => ['hot', 'iced'], 'size' => ['medium', 'large']]
            ],
        ];

        foreach ($products as $productData) {
            $category = $categories[$productData['category']];
            
            Product::create([
                'category_id' => $category->id,
                'name' => $productData['name'],
                'slug' => Str::slug($productData['name']),
                'description' => $productData['description'],
                'price' => $productData['price'],
                'cost' => $productData['cost'],
                'stock_quantity' => $productData['stock_quantity'],
                'low_stock_threshold' => 10,
                'unit' => 'piece',
                'is_active' => true,
                'track_stock' => true,
                'preparation_time' => $productData['preparation_time'],
                'variants' => $productData['variants'],
                'metadata' => [
                    'featured' => rand(0, 1) === 1,
                    'allergens' => [],
                ]
            ]);
        }
    }
}
