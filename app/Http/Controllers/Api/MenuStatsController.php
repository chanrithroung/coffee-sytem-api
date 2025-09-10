<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuStatsController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $stats = [
                'total_categories' => MenuCategory::count(),
                'active_categories' => MenuCategory::where('is_active', true)->count(),
                'visible_categories' => MenuCategory::where('is_visible', true)->count(),
                'total_items' => MenuItem::count(),
                'active_items' => MenuItem::where('is_available', true)->count(),
                'visible_items' => MenuItem::where('is_visible', true)->count(),
                'featured_items' => MenuItem::where('is_featured', true)->where('is_available', true)->count(),
                'on_sale_items' => MenuItem::where('is_on_sale', true)->where('is_available', true)->count(),
                'average_price' => MenuItem::where('is_available', true)->avg('price') ?? 0,
                'total_menu_value' => MenuItem::where('is_available', true)->sum('price'),
                'categories_with_items' => MenuCategory::has('menuItems')->count(),
                'items_by_category' => MenuCategory::withCount('menuItems')
                    ->orderBy('sort_order')
                    ->get()
                    ->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'icon' => $category->icon,
                            'color' => $category->color,
                            'item_count' => $category->menu_items_count,
                            'is_active' => $category->is_active,
                        ];
                    }),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats,
                'message' => 'Menu statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve menu statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function featuredItems(): JsonResponse
    {
        try {
            $featuredItems = MenuItem::with(['category', 'product'])
                ->where('is_featured', true)
                ->where('is_available', true)
                ->where('is_visible', true)
                ->orderBy('sort_order')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $featuredItems,
                'message' => 'Featured menu items retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve featured menu items',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function itemsByCategory(Request $request): JsonResponse
    {
        try {
            $categoryId = $request->get('category_id');
            
            if (!$categoryId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category ID is required'
                ], 400);
            }

            $items = MenuItem::with(['category', 'product'])
                ->where('category_id', $categoryId)
                ->where('is_available', true)
                ->where('is_visible', true)
                ->orderBy('sort_order')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $items,
                'message' => 'Menu items by category retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve menu items by category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q');
            
            if (!$query) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Search query is required'
                ], 400);
            }

            $items = MenuItem::with(['category', 'product'])
                ->where('is_available', true)
                ->where('is_visible', true)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%")
                      ->orWhereHas('category', function ($catQuery) use ($query) {
                          $catQuery->where('name', 'like', "%{$query}%");
                      });
                })
                ->orderBy('sort_order')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $items,
                'message' => 'Menu search completed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to search menu items',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
