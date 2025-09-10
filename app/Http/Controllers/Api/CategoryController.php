<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index(Request $request)
    {
        $query = Category::query();

        // Optimized query building
        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);
        $this->applyRelationships($query, $request);

        // Efficient pagination
        $perPage = min($request->get('per_page', 15), 100);
        $categories = $query->paginate($perPage);

        return [
            'status' => 'success',
            'data' => CategoryResource::collection($categories)->response()->getData(),
            'meta' => [
                'total_active' => Category::where('is_active', true)->count(),
                'total_inactive' => Category::where('is_active', false)->count(),
            ]
        ];
    }

    private function applyFilters($query, Request $request)
    {
        // Use database indexes efficiently
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            // Use full-text search or optimize LIKE queries
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }
    }

    private function applySorting($query, Request $request)
    {
        $sortBy = $request->get('sort_by', 'sort_order');
        $sortOrder = $request->get('sort_order', 'asc');

        // Ensure we're sorting by indexed columns
        $allowedSortColumns = ['id', 'name', 'sort_order', 'created_at', 'is_active'];
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('sort_order', 'asc');
        }
    }

    private function applyRelationships($query, Request $request)
    {
        // Eager load relationships to avoid N+1 queries
        $with = [];

        if ($request->boolean('include_products')) {
            $with[] = 'products:id,category_id,name,is_active';
        }

        if ($request->boolean('include_product_count')) {
            $query->withCount(['products', 'activeProducts']);
        }

        if (!empty($with)) {
            $query->with($with);
        }
    }

    /**
     * Store a newly created category with optimized performance
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|string|max:500',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return DB::transaction(function () use ($request, $validator) {
            $data = $validator->validated();

            // Auto-generate slug if not provided, with robust fallback for non-Latin names
            if (empty($data['slug'])) {
                $baseSlug = Str::slug($data['name']);
                if (empty($baseSlug)) {
                    // Fallback when slug is empty (e.g., Khmer or symbols-only names)
                    $baseSlug = 'cat-' . Str::random(8);
                }
                $slug = $baseSlug;
                $i = 1;
                while (Category::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $i;
                    $i++;
                }
                $data['slug'] = $slug;
            }

            // Optimized sort order calculation
            if (!isset($data['sort_order'])) {
                $data['sort_order'] = (Category::max('sort_order') ?? 0) + 1;
            }

            $category = Category::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Category created successfully',
                'data' => new CategoryResource($category)
            ], Response::HTTP_CREATED);
        });
    }

    /**
     * Display the specified category
     */
    public function show(Request $request, Category $category)
    {
        // Include relationships based on request
        $with = [];
        if ($request->boolean('include_products')) {
            $with[] = 'products';
        }
        if ($request->boolean('include_active_products')) {
            $with[] = 'activeProducts';
        }

        if (!empty($with)) {
            $category->load($with);
        }

        // Include counts
        if ($request->boolean('include_counts')) {
            $category->loadCount('products', 'activeProducts');
        }

        return response()->json([
            'status' => 'success',
            'data' => $category
        ]);
    }

    /**
     * Update the specified category with optimized performance
     */
    public function update(Request $request, Category $category)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:categories,name,' . $category->id,
            'slug' => 'sometimes|required|string|max:255|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|string|max:500',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return DB::transaction(function () use ($request, $validator, $category) {
            $data = $validator->validated();

            // Update slug if name changed, with robust fallback and uniqueness
            if (isset($data['name']) && !isset($data['slug'])) {
                $baseSlug = Str::slug($data['name']);
                if (empty($baseSlug)) {
                    $baseSlug = 'cat-' . Str::random(8);
                }
                $slug = $baseSlug;
                $i = 1;
                while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                    $slug = $baseSlug . '-' . $i;
                    $i++;
                }
                $data['slug'] = $slug;
            }

            $category->update($data);

            // Clear relevant caches
            $this->clearCategoryCache($category->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Category updated successfully',
                'data' => new CategoryResource($category->fresh())
            ]);
        });
    }

    /**
     * Remove the specified category with optimized checks
     */
    public function destroy(Category $category)
    {
        return DB::transaction(function () use ($category) {
            // Efficient product count check using exists
            if ($category->products()->exists()) {
                $productsCount = $category->products()->count();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete category with associated products',
                    'data' => [
                        'products_count' => $productsCount
                    ]
                ], Response::HTTP_CONFLICT);
            }

            $category->delete();

            // Clear relevant caches
            $this->clearCategoryCache($category->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Category deleted successfully'
            ]);
        });
    }

    /**
     * Bulk operations with optimized performance
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'categories' => 'required|array|max:100', // Limit bulk operations
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.sort_order' => 'nullable|integer|min:0',
            'categories.*.is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return DB::transaction(function () use ($request) {
            $updated = 0;
            $categoryIds = collect($request->categories)->pluck('id')->toArray();

            // Bulk load categories to reduce queries
            $categories = Category::whereIn('id', $categoryIds)->get()->keyBy('id');

            foreach ($request->categories as $categoryData) {
                $category = $categories->get($categoryData['id']);
                if ($category) {
                    $updateData = array_filter([
                        'sort_order' => $categoryData['sort_order'] ?? null,
                        'is_active' => isset($categoryData['is_active']) ? $categoryData['is_active'] : null,
                    ]);

                    if (!empty($updateData)) {
                        $category->update($updateData);
                        $updated++;
                    }
                }
            }

            // Clear relevant caches
            $this->clearCategoryCache();

            return response()->json([
                'status' => 'success',
                'message' => "Updated {$updated} categories successfully",
                'data' => [
                    'updated_count' => $updated
                ]
            ]);
        });
    }

    /**
     * Get category statistics
     */
    public function stats()
    {
        // Use single query with conditional aggregates for better performance
        $categoryStats = Category::selectRaw('
            COUNT(*) as total_categories,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_categories,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_categories
        ')->first();

        // Optimized queries for relationships
        $categoriesWithProducts = Category::has('products')->count();
        $emptyCategories = Category::doesntHave('products')->count();
        $totalProducts = DB::table('products')->count();

        $stats = [
            'total_categories' => $categoryStats->total_categories,
            'active_categories' => $categoryStats->active_categories,
            'inactive_categories' => $categoryStats->inactive_categories,
            'categories_with_products' => $categoriesWithProducts,
            'empty_categories' => $emptyCategories,
            'total_products' => $totalProducts,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    /**
     * Clear category-related cache entries
     */
    private function clearCategoryCache($categoryId = null)
    {
        // Cache clearing logic removed since we're not using caching
    }

    private function clearCachePattern($pattern)
    {
        // Cache clearing logic removed since we're not using caching
    }
}
