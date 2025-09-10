<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Table;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats(Request $request)
    {
        try {
            // Get the authenticated user
            $user = $request->user();

            // Log user info for debugging
            Log::info('Dashboard stats requested by user:', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'user_name' => $user->name
            ]);

            // Debug the queries being executed
            $todayRevenueQuery = Order::whereDate('created_at', Carbon::today())
                ->where('payment_status', 'paid');

            $todayRevenueCount = $todayRevenueQuery->count();
            $todayRevenueSum = (float) $todayRevenueQuery->sum('total_amount');

            Log::info('Today revenue query debug:', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'query_count' => $todayRevenueCount,
                'query_sum' => $todayRevenueSum,
                'sql' => $todayRevenueQuery->toSql(),
                'bindings' => $todayRevenueQuery->getBindings()
            ]);

            // Ensure we're getting all orders, not user-specific ones
            $stats = [
                'total_products' => Product::count(),
                'total_orders' => Order::count(),
                'total_users' => User::count(),
                'total_tables' => Table::count(),
                'total_revenue' => (float) Order::where('payment_status', 'paid')
                    ->sum('total_amount'),
                'recent_orders' => Order::with(['table', 'items.product'])
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(function ($order) {
                        return [
                            'id' => $order->id,
                            'order_number' => $order->order_number,
                            'customer_name' => $order->customer_name,
                            'table_id' => $order->table_id,
                            'table_name' => $order->table->name ?? 'N/A',
                            'total_amount' => (float) $order->total_amount,
                            'status' => $order->status,
                            'payment_status' => $order->payment_status,
                            'ordered_at' => $order->ordered_at,
                            'items_count' => $order->items->count(),
                        ];
                    }),
                'today_orders' => Order::whereDate('created_at', Carbon::today())->count(),
                'today_revenue' => $todayRevenueSum,
                'pending_orders' => Order::where('status', 'pending')->count(),
                'completed_orders' => Order::where('status', 'completed')->count(),
                'table_status' => [
                    'available' => Table::where('status', 'available')->where('is_active', true)->count(),
                    'occupied' => Table::where('status', 'occupied')->where('is_active', true)->count(),
                    'reserved' => Table::where('status', 'reserved')->where('is_active', true)->count(),
                    'cleaning' => Table::where('status', 'cleaning')->where('is_active', true)->count(),
                ],
            ];

            Log::info('Dashboard stats calculated:', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'today_revenue' => $stats['today_revenue'],
                'today_orders' => $stats['today_orders'],
                'total_revenue' => $stats['total_revenue'],
                'total_orders' => $stats['total_orders']
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Dashboard statistics retrieved successfully',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error in dashboard stats:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get income data for charts
     */
    public function income(Request $request)
    {
        try {
            $period = $request->get('period', 'week'); // week, month, year

            $incomeData = [];

            switch ($period) {
                case 'week':
                    // Last 7 days
                    for ($i = 6; $i >= 0; $i--) {
                        $date = Carbon::now()->subDays($i);
                        $revenue = Order::whereDate('created_at', $date)
                            ->where('payment_status', 'paid')
                            ->sum('total_amount');

                        $incomeData[] = [
                            'date' => $date->format('Y-m-d'),
                            'day' => $date->format('D'),
                            'revenue' => (float) $revenue
                        ];
                    }
                    break;

                case 'month':
                    // Last 30 days
                    for ($i = 29; $i >= 0; $i--) {
                        $date = Carbon::now()->subDays($i);
                        $revenue = Order::whereDate('created_at', $date)
                            ->where('payment_status', 'paid')
                            ->sum('total_amount');

                        $incomeData[] = [
                            'date' => $date->format('Y-m-d'),
                            'day' => $date->format('M j'),
                            'revenue' => (float) $revenue
                        ];
                    }
                    break;

                default:
                    // Last 12 months
                    for ($i = 11; $i >= 0; $i--) {
                        $date = Carbon::now()->subMonths($i);
                        $revenue = Order::whereYear('created_at', $date->year)
                            ->whereMonth('created_at', $date->month)
                            ->where('payment_status', 'paid')
                            ->sum('total_amount');

                        $incomeData[] = [
                            'date' => $date->format('Y-m'),
                            'day' => $date->format('M Y'),
                            'revenue' => (float) $revenue
                        ];
                    }
                    break;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Income data retrieved successfully',
                'data' => $incomeData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve income data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent orders
     */
    public function recentOrders(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);

            $orders = Order::with(['table', 'items.product'])
                ->latest()
                ->take($limit)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->customer_name,
                        'table_id' => $order->table_id,
                        'table_name' => $order->table->name ?? 'N/A',
                        'total_amount' => (float) $order->total_amount,
                        'status' => $order->status,
                        'payment_status' => $order->payment_status,
                        'ordered_at' => $order->ordered_at,
                        'created_at' => $order->created_at,
                        'items_count' => $order->items->count(),
                        'items' => $order->items->map(function ($item) {
                            return [
                                'product_name' => $item->product_name,
                                'quantity' => (int) $item->quantity,
                                'unit_price' => (float) $item->unit_price,
                                'line_total' => (float) $item->line_total,
                            ];
                        })
                    ];
                });

            return response()->json([
                'status' => 'success',
                'message' => 'Recent orders retrieved successfully',
                'data' => $orders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve recent orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get top selling products
     */
    public function topSellingProducts(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);
            $period = $request->get('period', 'all'); // all, week, month, year

            // Build the query based on period
            $query = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->select(
                    'products.id',
                    'products.name',
                    'products.price',
                    DB::raw('SUM(order_items.quantity) as total_quantity'),
                    DB::raw('SUM(order_items.line_total) as total_revenue')
                )
                ->groupBy('products.id', 'products.name', 'products.price')
                ->orderBy('total_quantity', 'desc')
                ->limit($limit);

            // Apply period filter
            switch ($period) {
                case 'week':
                    $query->join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->where('orders.created_at', '>=', Carbon::now()->subWeek());
                    break;
                case 'month':
                    $query->join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->where('orders.created_at', '>=', Carbon::now()->subMonth());
                    break;
                case 'year':
                    $query->join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->where('orders.created_at', '>=', Carbon::now()->subYear());
                    break;
                default:
                    // No additional filtering for 'all' period
                    $query->join('orders', 'order_items.order_id', '=', 'orders.id');
                    break;
            }

            $topProducts = $query->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Top selling products retrieved successfully',
                'data' => $topProducts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve top selling products',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
