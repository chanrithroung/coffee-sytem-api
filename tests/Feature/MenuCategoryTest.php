<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\MenuCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MenuCategoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_sets_default_available_days_when_not_provided()
    {
        $category = MenuCategory::create([
            'name' => 'Test Category',
            'description' => 'Test Description',
        ]);

        $this->assertEquals([0, 1, 2, 3, 4, 5, 6], $category->available_days);
    }

    /** @test */
    public function it_allows_custom_available_days()
    {
        $category = MenuCategory::create([
            'name' => 'Test Category',
            'description' => 'Test Description',
            'available_days' => [1, 3, 5], // Only Monday, Wednesday, Friday
        ]);

        $this->assertEquals([1, 3, 5], $category->available_days);
    }

    /** @test */
    public function it_handles_null_available_days()
    {
        $category = new MenuCategory();
        $category->name = 'Test Category';
        $category->description = 'Test Description';
        $category->available_days = null;
        $category->save();

        // Should still use the default
        $this->assertEquals([0, 1, 2, 3, 4, 5, 6], $category->available_days);
    }
}
