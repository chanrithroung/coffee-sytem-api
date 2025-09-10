<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\MenuCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class MenuItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = MenuItem::with(['category', 'product'])
                ->orderBy('sort_order');

            // Filter by category
            if ($request->has('category_id') && $request->category_id !== 'all') {
                $query->where('category_id', $request->category_id);
            }

            // Filter by availability
            if ($request->has('available')) {
                $query->where('is_available', $request->boolean('available'));
            }

            // Filter by featured
            if ($request->has('featured')) {
                $query->where('is_featured', $request->boolean('featured'));
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $menuItems = $query->get();

            return response()->json([
                'status' => 'success',
                'data' => $menuItems,
                'message' => 'Menu items retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve menu items',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'category_id' => 'required|exists:menu_categories,id',
                'product_id' => 'nullable|exists:products,id',
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'is_on_sale' => 'boolean',
                'is_available' => 'boolean',
                'is_visible' => 'boolean',
                'is_featured' => 'boolean',
                'sort_order' => 'nullable|integer|min:0',
                'tags' => 'nullable|array',
                'tags.*' => 'string',
                'allergens' => 'nullable|array',
                'allergens.*' => 'string',
                'nutrition_info' => 'nullable|array',
                'preparation_time' => 'nullable|integer|min:1',
                'customizations' => 'nullable|array',
                'images' => 'nullable|array',
                'images.*' => 'string',
            ]);

            $menuItem = MenuItem::create($validated);

            return response()->json([
                'status' => 'success',
                'data' => $menuItem->load(['category', 'product']),
                'message' => 'Menu item created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create menu item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(MenuItem $menuItem): JsonResponse
    {
        try {
            $menuItem->load(['category', 'product']);

            return response()->json([
                'status' => 'success',
                'data' => $menuItem,
                'message' => 'Menu item retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve menu item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, MenuItem $menuItem): JsonResponse
    {
        try {
            $validated = $request->validate([
                'category_id' => 'sometimes|required|exists:menu_categories,id',
                'product_id' => 'nullable|exists:products,id',
                'name' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'price' => 'sometimes|required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'is_on_sale' => 'boolean',
                'is_available' => 'boolean',
                'is_visible' => 'boolean',
                'is_featured' => 'boolean',
                'sort_order' => 'nullable|integer|min:0',
                'tags' => 'nullable|array',
                'tags.*' => 'string',
                'allergens' => 'nullable|array',
                'allergens.*' => 'string',
                'nutrition_info' => 'nullable|array',
                'preparation_time' => 'nullable|integer|min:1',
                'customizations' => 'nullable|array',
                'images' => 'nullable|array',
                'images.*' => 'string',
            ]);

            $menuItem->update($validated);

            return response()->json([
                'status' => 'success',
                'data' => $menuItem->fresh()->load(['category', 'product']),
                'message' => 'Menu item updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update menu item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(MenuItem $menuItem): JsonResponse
    {
        try {
            $menuItem->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Menu item deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete menu item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function toggleAvailability(MenuItem $menuItem): JsonResponse
    {
        try {
            $menuItem->update([
                'is_available' => !$menuItem->is_available
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $menuItem->fresh(),
                'message' => 'Menu item availability toggled successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle menu item availability',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function toggleFeatured(MenuItem $menuItem): JsonResponse
    {
        try {
            $menuItem->update([
                'is_featured' => !$menuItem->is_featured
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $menuItem->fresh(),
                'message' => 'Menu item featured status toggled successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle menu item featured status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function reorder(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'item_ids' => 'required|array',
                'item_ids.*' => 'exists:menu_items,id'
            ]);

            foreach ($validated['item_ids'] as $index => $id) {
                MenuItem::where('id', $id)->update(['sort_order' => $index]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Menu items reordered successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reorder menu items',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
