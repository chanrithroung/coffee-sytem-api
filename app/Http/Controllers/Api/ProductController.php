<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
class ProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // Optimized query building
        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);
        $this->applyRelationships($query, $request);

        // Efficient pagination
        $perPage = min($request->get('per_page', 15), 100);
        $products = $query->paginate($perPage);

        return [
            'status' => 'success',
            'data' => ProductResource::collection($products)->response()->getData(),
            'meta' => [
                'total_active' => Product::where('is_active', true)->count(),
                'total_inactive' => Product::where('is_active', false)->count(),
            ]
        ];
    }

    private function applyFilters($query, Request $request)
    {
        // Use database indexes efficiently
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }
    }

    private function applySorting($query, Request $request)
    {
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');

        // Ensure we're sorting by indexed columns
        $allowedSortColumns = ['id', 'name', 'price', 'created_at', 'is_active'];

        if ($sortBy === 'category') {
            $query->join('categories', 'products.category_id', '=', 'categories.id')
                ->orderBy('categories.name', $sortOrder)
                ->select('products.*');
        } elseif (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc');
        }
    }

    private function applyRelationships($query, Request $request)
    {
        // Eager load relationships to avoid N+1 queries
        $with = ['category:id,name,slug'];

        if ($request->boolean('include_order_history')) {
            $with[] = 'orderItems:id,product_id,order_id,quantity,unit_price,subtotal,created_at';
            $with[] = 'orderItems.order:id,order_number,status,created_at';
        }

        if ($request->boolean('include_sales_stats')) {
            $query->withCount('orderItems');
        }

        $query->with($with);
    }

    /**
     * Store a newly created product with optimized performance
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'description' => 'nullable|string|max:2000',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'barcode' => 'nullable|string|max:100|unique:products,barcode',
            'price' => 'required|numeric|min:0|max:999999.99',
            'cost' => 'nullable|numeric|min:0|max:999999.99',
            'unit' => 'nullable|string|max:50',
            'images' => 'nullable|array',
            'images.*' => 'string|max:500',
            'is_active' => 'nullable|boolean',
            'preparation_time' => 'nullable|integer|min:1|max:120',
            'variants' => 'nullable|array',
            'nutrition_info' => 'nullable|array',
            'allergens' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return DB::transaction(function () use ($validator, $request) {
            $data = $validator->validated();

            // Set defaults
            $data['unit'] = $data['unit'] ?? 'piece';
            $data['preparation_time'] = $data['preparation_time'] ?? 5;

            // Auto-generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $product = Product::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully',
                'data' => new ProductResource($product->load('category'))
            ], Response::HTTP_CREATED);
        });
    }

    /**
     * Display the specified product with optimized loading
     */
    public function show(Request $request, Product $product)
    {
        // Efficient relationship loading
        $with = ['category:id,name,slug'];

        if ($request->boolean('include_order_history')) {
            $with[] = 'orderItems:id,product_id,order_id,quantity,unit_price,subtotal,created_at';
            $with[] = 'orderItems.order:id,order_number,status,created_at';
        }

        if ($request->boolean('include_sales_stats')) {
            $product->loadCount('orderItems');
        }

        $product->load($with);

        return response()->json([
            'status' => 'success',
            'data' => new ProductResource($product)
        ]);
    }

    /**
     * Update the specified product with optimized performance
     */
    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'sometimes|required|exists:categories,id',
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:products,slug,' . $product->id,
            'description' => 'nullable|string|max:2000',
            'sku' => 'sometimes|required|string|max:100|unique:products,sku,' . $product->id,
            'barcode' => 'nullable|string|max:100|unique:products,barcode,' . $product->id,
            'price' => 'sometimes|required|numeric|min:0|max:999999.99',
            'cost' => 'nullable|numeric|min:0|max:999999.99',
            'unit' => 'nullable|string|max:50',
            'images' => 'nullable|array',
            'images.*' => 'string|max:500',
            'is_active' => 'nullable|boolean',
            'preparation_time' => 'nullable|integer|min:1|max:120',
            'variants' => 'nullable|array',
            'nutrition_info' => 'nullable|array',
            'allergens' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return DB::transaction(function () use ($validator, $product, $request) {
            $data = $validator->validated();

            // Update slug if name changed
            if (isset($data['name']) && !isset($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $product->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Product updated successfully',
                'data' => new ProductResource($product->fresh(['category']))
            ]);
        });
    }

    /**
     * Remove the specified product with optimized checks
     */
    public function destroy(Product $product)
    {
        return DB::transaction(function () use ($product) {
            // Efficient order items check using exists
            if ($product->orderItems()->exists()) {
                $orderItemsCount = $product->orderItems()->count();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete product with existing orders',
                    'data' => [
                        'order_items_count' => $orderItemsCount
                    ]
                ], Response::HTTP_CONFLICT);
            }

            $product->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Product deleted successfully'
            ]);
        });
    }

    /**
     * Get products by category
     */
    public function byCategory(Request $request, Category $category)
    {
        $query = $category->products();

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');

        $allowedSortColumns = ['id', 'name', 'price', 'created_at'];
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc');
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $products = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => ProductResource::collection($products),
            'meta' => [
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'product_count' => $category->products()->count()
                ]
            ]
        ]);
    }

    /**
     * Bulk update products with optimized performance
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'products' => 'required|array|max:100', // Limit bulk operations
            'products.*.id' => 'required|exists:products,id',
            'products.*.price' => 'nullable|numeric|min:0',
            'products.*.is_active' => 'nullable|boolean',
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
            $productIds = collect($request->products)->pluck('id')->toArray();

            // Bulk load products to reduce queries
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            foreach ($request->products as $productData) {
                $product = $products->get($productData['id']);
                if ($product) {
                    $updateData = array_filter([
                        'price' => $productData['price'] ?? null,
                        'is_active' => isset($productData['is_active']) ? $productData['is_active'] : null,
                    ]);

                    if (!empty($updateData)) {
                        $product->update($updateData);
                        $updated++;
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => "Updated {$updated} products successfully",
                'data' => [
                    'updated_count' => $updated
                ]
            ]);
        });
    }

    /**
     * Get product statistics
     */
    public function stats()
    {
        // Use single queries with conditional aggregates for better performance
        $productStats = Product::selectRaw('
            COUNT(*) as total_products,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_products,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_products,
            SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
            SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= low_stock_threshold THEN 1 ELSE 0 END) as low_stock,
            AVG(price) as average_price,
            SUM(price * stock_quantity) as inventory_value
        ')->first();

        // Additional optimized queries
        $categoryCount = DB::table('categories')
            ->join('products', 'categories.id', '=', 'products.category_id')
            ->distinct('categories.id')
            ->count();

        $stats = [
            'total_products' => $productStats->total_products,
            'active_products' => $productStats->active_products,
            'inactive_products' => $productStats->inactive_products,
            'out_of_stock' => $productStats->out_of_stock,
            'low_stock' => $productStats->low_stock,
            'categories_with_products' => $categoryCount,
            'average_price' => round($productStats->average_price, 2),
            'inventory_value' => round($productStats->inventory_value, 2),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    /**
     * Sync products (for real-time updates)
     */
    public function sync(Request $request)
    {
        $lastSync = $request->get('last_sync');

        $query = Product::with(['category']);

        if ($lastSync) {
            $query->where('updated_at', '>', Carbon::parse($lastSync));
        }

        $products = $query->orderBy('updated_at', 'desc')->limit(50)->get();

        return response()->json([
            'status' => 'success',
            'data' => ProductResource::collection($products),
            'sync_timestamp' => now()->toISOString()
        ]);
    }
}
