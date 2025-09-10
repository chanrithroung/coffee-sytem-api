<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MenuCategoryController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\MenuStatsController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\WebSocketController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public health check
Route::get('/health/public', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Public API endpoint is working',
        'timestamp' => now(),
        'version' => '1.0.0',
    ]);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Authentication routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // User management routes
    Route::apiResource('users', UserController::class);

    // Category management routes
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    Route::post('/categories/bulk-update', [CategoryController::class, 'bulkUpdate']);
    Route::get('/categories-stats', [CategoryController::class, 'stats']);

    // Product management routes
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    Route::patch('/products/{product}/stock', [ProductController::class, 'updateStock']);
    Route::get('/products/category/{category}', [ProductController::class, 'byCategory']);
    Route::get('/products-low-stock', [ProductController::class, 'lowStock']); // Cache low stock for 1 minute only
    Route::post('/products/bulk-update', [ProductController::class, 'bulkUpdate']);
    Route::get('/products-stats', [ProductController::class, 'stats']);

    // Table management routes - Allow sale role to view and update
    Route::get('/tables', [TableController::class, 'index']);
    Route::get('/tables/{table}', [TableController::class, 'show']);
    Route::patch('/tables/{table}/status', [TableController::class, 'updateStatus']);
    Route::get('/tables-available', [TableController::class, 'available']);

    // Admin-only table operations
    Route::middleware('role:admin')->group(function () {
        Route::post('/tables', [TableController::class, 'store']);
        Route::put('/tables/{table}', [TableController::class, 'update']);
        Route::delete('/tables/{table}', [TableController::class, 'destroy']);
        Route::post('/tables/bulk-update', [TableController::class, 'bulkUpdate']);
        Route::get('/tables-stats', [TableController::class, 'stats']);
    });

    // Order management routes
    Route::apiResource('orders', OrderController::class);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::patch('/orders/{order}/payment', [OrderController::class, 'addPayment']);
    Route::post('/orders/bulk-update', [OrderController::class, 'bulkUpdate']);
    Route::get('/orders/table/{table}', [OrderController::class, 'byTable']);
    Route::get('/orders/today', [OrderController::class, 'today']);
    Route::get('/orders-stats', [OrderController::class, 'stats']);

    // Real-time data sync endpoints
    Route::get('/sync/orders', [OrderController::class, 'sync']);
    Route::get('/sync/tables', [TableController::class, 'sync']);
    Route::get('/sync/products', [ProductController::class, 'sync']);

    // Dashboard endpoints - No role restrictions, accessible to all authenticated users
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/income', [DashboardController::class, 'income']);
    Route::get('/dashboard/recent-orders', [DashboardController::class, 'recentOrders']);
    Route::get('/dashboard/top-selling-products', [DashboardController::class, 'topSellingProducts']);

    // Menu Management routes
    Route::apiResource('menu-categories', MenuCategoryController::class);
    Route::patch('/menu-categories/{menuCategory}/toggle-availability', [MenuCategoryController::class, 'toggleAvailability']);
    Route::post('/menu-categories/reorder', [MenuCategoryController::class, 'reorder']);

    Route::apiResource('menu-items', MenuItemController::class);
    Route::patch('/menu-items/{menuItem}/toggle-availability', [MenuItemController::class, 'toggleAvailability']);
    Route::patch('/menu-items/{menuItem}/toggle-featured', [MenuItemController::class, 'toggleFeatured']);
    Route::post('/menu-items/reorder', [MenuItemController::class, 'reorder']);

    // Menu Statistics and Search
    Route::get('/menu-stats', [MenuStatsController::class, 'index']);
    Route::get('/menu-featured', [MenuStatsController::class, 'featuredItems']);
    Route::get('/menu-by-category', [MenuStatsController::class, 'itemsByCategory']);
    Route::get('/menu-search', [MenuStatsController::class, 'search']);

    // Notifications routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::get('/notifications/settings', [NotificationController::class, 'getSettings']);
    Route::patch('/notifications/settings', [NotificationController::class, 'updateSettings']);

    // Broadcasting authentication
    Route::post('/broadcasting/auth', function (Request $request) {
        return response()->json(['status' => 'success']);
    });

    // Test notification endpoint
    Route::post('/notifications/test', [NotificationController::class, 'testNotification']);

    // WebSocket test endpoints
    Route::post('/websocket/test', [WebSocketController::class, 'sendTestNotification']);
    Route::post('/websocket/order', [WebSocketController::class, 'sendOrderNotification']);

    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'success',
            'message' => 'Protected API endpoint is working',
            'user' => auth()->user()->name,
            'timestamp' => now(),
            'database' => \Illuminate\Support\Facades\DB::connection()->getPdo() ? 'connected' : 'disconnected',
        ]);
    });

    // System Settings routes
    Route::get('/system-settings', [SettingsController::class, 'index']);
    Route::patch('/system-settings', [SettingsController::class, 'update']);
});
