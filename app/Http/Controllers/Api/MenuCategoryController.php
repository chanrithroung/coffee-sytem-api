<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class MenuCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $categories = MenuCategory::orderBy('sort_order')
                ->withCount('menuItems')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $categories,
                'message' => 'Menu categories retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve menu categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:menu_categories',
                'description' => 'required|string',
                'icon' => 'nullable|string|max:10',
                'color' => 'nullable|string|max:7',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'boolean',
                'is_visible' => 'boolean',
                'available_from' => 'nullable|date_format:H:i:s',
                'available_to' => 'nullable|date_format:H:i:s',
                'available_days' => 'nullable|array',
                'available_days.*' => 'integer|min:0|max:6',
            ]);

            $category = MenuCategory::create($validated);

            return response()->json([
                'status' => 'success',
                'data' => $category,
                'message' => 'Menu category created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create menu category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(MenuCategory $menuCategory): JsonResponse
    {
        try {
            $category = $menuCategory->load('menuItems');

            return response()->json([
                'status' => 'success',
                'data' => $category,
                'message' => 'Menu category retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve menu category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, MenuCategory $menuCategory): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('menu_categories')->ignore($menuCategory->id)],
                'description' => 'required|string',
                'icon' => 'nullable|string|max:10',
                'color' => 'nullable|string|max:7',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'boolean',
                'is_visible' => 'boolean',
                'available_from' => 'nullable|date_format:H:i:s',
                'available_to' => 'nullable|date_format:H:i:s',
                'available_days' => 'nullable|array',
                'available_days.*' => 'integer|min:0|max:6',
            ]);

            $menuCategory->update($validated);

            return response()->json([
                'status' => 'success',
                'data' => $menuCategory->fresh(),
                'message' => 'Menu category updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update menu category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(MenuCategory $menuCategory): JsonResponse
    {
        try {
            $menuCategory->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Menu category deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete menu category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function toggleAvailability(MenuCategory $menuCategory): JsonResponse
    {
        try {
            $menuCategory->update([
                'is_active' => !$menuCategory->is_active
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $menuCategory->fresh(),
                'message' => 'Menu category availability toggled successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle menu category availability',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function reorder(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'category_ids' => 'required|array',
                'category_ids.*' => 'exists:menu_categories,id'
            ]);

            foreach ($validated['category_ids'] as $index => $id) {
                MenuCategory::where('id', $id)->update(['sort_order' => $index]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Menu categories reordered successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reorder menu categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
