<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Table;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderController extends Controller
{
    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $query = Order::query();

        // Apply filters
        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);
        $this->applyRelationships($query, $request);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $orders = $query->paginate($perPage);

        return [
            'status' => 'success',
            'data' => OrderResource::collection($orders)->response()->getData(),
            'meta' => [
                'total_today' => Order::today()->count(),
                'pending_count' => Order::pending()->count(),
                'revenue_today' => Order::today()->sum('total_amount'),
                'active_orders' => Order::active()->count(),
            ]
        ];
    }

    private function applyFilters($query, Request $request)
    {
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('order_type')) {
            $query->where('order_type', $request->get('order_type'));
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->get('payment_status'));
        }

        if ($request->has('table_id')) {
            $query->where('table_id', $request->get('table_id'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('ordered_at', '>=', $request->get('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('ordered_at', '<=', $request->get('date_to'));
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }
    }

    private function applySorting($query, Request $request)
    {
        $sortBy = $request->get('sort_by', 'ordered_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSortColumns = ['id', 'order_number', 'status', 'total_amount', 'ordered_at', 'created_at'];

        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('ordered_at', 'desc');
        }
    }

    private function applyRelationships($query, Request $request)
    {
        $with = ['user:id,name,email', 'table:id,table_number,capacity', 'items.product:id,name,price'];

        if ($request->boolean('include_customer_details')) {
            // Already included in main query
        }

        $query->with($with);
    }

    /**
     * Store a newly created order
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table_id' => 'nullable|exists:tables,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'order_type' => 'required|in:dine_in,takeaway,delivery',
            'status' => 'sometimes|in:pending,confirmed,preparing,ready,served,completed,cancelled',
            'payment_status' => 'sometimes|in:pending,paid,partial,refunded',
            'payment_method' => 'sometimes|in:cash,card,digital_wallet,bank_transfer',
            'notes' => 'nullable|string|max:1000',
            'special_instructions' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:100',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
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
            $items = $data['items'];
            unset($data['items']);

            // Set user
            $data['user_id'] = auth()->id();

            // Calculate totals
            $subtotal = 0;
            $validatedItems = [];

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Check availability
                if (!$product->is_active) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Product {$product->name} is not available"
                    ], Response::HTTP_BAD_REQUEST);
                }

                // Check stock if tracking is enabled
                if ($product->track_stock && $product->stock_quantity < $item['quantity']) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Insufficient stock for {$product->name}. Available: {$product->stock_quantity}"
                    ], Response::HTTP_BAD_REQUEST);
                }

                $unitPrice = $item['unit_price'] ?? $product->price;
                $itemSubtotal = $unitPrice * $item['quantity'];
                $subtotal += $itemSubtotal;

                $validatedItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'line_total' => $itemSubtotal,
                    'special_instructions' => $item['notes'] ?? null,
                    'status' => 'pending',
                    'preparation_time' => $product->preparation_time,
                ];
            }

            // Calculate tax and service charge (configurable)
            $taxRate = config('app.tax_rate', 0.10); // 10% default
            $serviceChargeRate = config('app.service_charge_rate', 0.05); // 5% default

            $data['subtotal'] = $subtotal;
            $data['tax_amount'] = $subtotal * $taxRate;
            $data['service_charge'] = $subtotal * $serviceChargeRate;
            $data['total_amount'] = $subtotal + $data['tax_amount'] + $data['service_charge'];

            // Automatically mark all new orders as paid by default
            // This implements the requirement that all transactions should be auto-marked as paid
            $data['payment_status'] = 'paid';
            $data['paid_amount'] = $data['total_amount'];

            // Set default payment method if not provided
            if (!isset($data['payment_method'])) {
                $data['payment_method'] = 'cash';
            }

            // Set default status if not provided
            if (!isset($data['status'])) {
                $data['status'] = 'pending';
            }

            // Create order
            $order = Order::create($data);

            // Create order items and update stock
            foreach ($validatedItems as $itemData) {
                $order->items()->create($itemData);

                // Update product stock if tracking is enabled
                $product = Product::find($itemData['product_id']);
                if ($product->track_stock) {
                    $product->decrement('stock_quantity', $itemData['quantity']);
                }
            }

            // Update table status if dine-in
            if ($data['order_type'] === 'dine_in' && $data['table_id']) {
                Table::where('id', $data['table_id'])->update(['status' => 'occupied']);
            }

            // Clear caches
            $this->clearOrderCache();

            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully and automatically marked as paid',
                'data' => new OrderResource($order->load(['user', 'table', 'items.product']))
            ], Response::HTTP_CREATED);
        });
    }

    /**
     * Display the specified order
     */
    public function show(Request $request, Order $order)
    {
        $order->load(['user', 'table', 'items.product']);
        $data = new OrderResource($order);

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    /**
     * Update the specified order
     */
    public function update(Request $request, Order $order)
    {
        $validator = Validator::make($request->all(), [
            'customer_name' => 'sometimes|string|max:255',
            'customer_phone' => 'sometimes|string|max:20',
            'customer_email' => 'sometimes|email|max:255',
            'notes' => 'nullable|string|max:1000',
            'special_instructions' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check if order can be updated
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot update completed or cancelled orders'
            ], Response::HTTP_BAD_REQUEST);
        }

        $order->update($validator->validated());
        $this->clearOrderCache($order->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Order updated successfully',
            'data' => new OrderResource($order->fresh(['user', 'table', 'items.product']))
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,preparing,ready,served,completed,cancelled',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $newStatus = $request->get('status');

        return DB::transaction(function () use ($order, $newStatus, $request) {
            switch ($newStatus) {
                case 'confirmed':
                    $order->confirm();
                    break;
                case 'preparing':
                    $order->startPreparing();
                    break;
                case 'ready':
                    $order->markReady();
                    break;
                case 'served':
                    $order->markServed();
                    break;
                case 'completed':
                    $order->complete();
                    // Free up table if dine-in
                    if ($order->table_id && $order->order_type === 'dine_in') {
                        Table::where('id', $order->table_id)->update(['status' => 'available']);
                    }
                    break;
                case 'cancelled':
                    $order->cancel();
                    // Restore stock for cancelled orders
                    foreach ($order->items as $item) {
                        if ($item->product->track_stock) {
                            $item->product->increment('stock_quantity', $item->quantity);
                        }
                    }
                    // Free up table if dine-in
                    if ($order->table_id && $order->order_type === 'dine_in') {
                        Table::where('id', $order->table_id)->update(['status' => 'available']);
                    }
                    break;
            }

            $this->clearOrderCache($order->id);

            return response()->json([
                'status' => 'success',
                'message' => "Order status updated to {$newStatus}",
                'data' => new OrderResource($order->fresh(['user', 'table', 'items.product']))
            ]);
        });
    }

    /**
     * Add payment to order
     */
    public function addPayment(Request $request, Order $order)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,digital_wallet,bank_transfer',
            'reference' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $amount = $request->get('amount');
        $paymentMethod = $request->get('payment_method');

        if ($amount > $order->remaining_amount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment amount cannot exceed remaining amount',
                'data' => [
                    'remaining_amount' => $order->remaining_amount,
                    'attempted_amount' => $amount
                ]
            ], Response::HTTP_BAD_REQUEST);
        }

        return DB::transaction(function () use ($order, $amount, $paymentMethod, $request) {
            $order->paid_amount += $amount;
            $order->payment_method = $paymentMethod;

            if ($order->paid_amount >= $order->total_amount) {
                $order->payment_status = 'paid';
            } else {
                $order->payment_status = 'partial';
            }

            $order->save();
            $this->clearOrderCache($order->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment added successfully',
                'data' => [
                    'order' => new OrderResource($order->fresh(['user', 'table', 'items.product'])),
                    'payment_details' => [
                        'amount_paid' => $amount,
                        'total_paid' => $order->paid_amount,
                        'remaining_amount' => $order->remaining_amount,
                        'is_fully_paid' => $order->is_paid
                    ]
                ]
            ]);
        });
    }

    /**
     * Get orders by table
     */
    public function byTable(Request $request, Table $table)
    {
        $query = $table->orders()->with(['user', 'items.product']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('ordered_at', '>=', $request->get('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('ordered_at', '<=', $request->get('date_to'));
        }

        // Sorting
        $query->orderBy('ordered_at', 'desc');

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $orders = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => OrderResource::collection($orders),
            'meta' => [
                'table' => [
                    'id' => $table->id,
                    'table_number' => $table->table_number,
                    'order_count' => $table->orders()->count()
                ]
            ]
        ]);
    }

    /**
     * Get today's orders
     */
    public function today(Request $request)
    {
        $query = Order::today()->with(['user', 'table', 'items.product']);

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        $data = $query->orderBy('ordered_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => OrderResource::collection($data),
            'meta' => [
                'date' => now()->format('Y-m-d'),
                'total_orders' => $data->count(),
                'total_revenue' => $data->sum('total_amount'),
                'completed_orders' => $data->where('status', 'completed')->count(),
            ]
        ]);
    }

    /**
     * Get order statistics
     */
    public function stats(Request $request)
    {
        $data = [
            'today' => [
                'total_orders' => Order::today()->count(),
                'total_revenue' => Order::today()->sum('total_amount'),
                'completed_orders' => Order::today()->completed()->count(),
                'pending_orders' => Order::today()->pending()->count(),
                'average_order_value' => Order::today()->avg('total_amount'),
            ],
            'this_week' => [
                'total_orders' => Order::whereBetween('ordered_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'total_revenue' => Order::whereBetween('ordered_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('total_amount'),
            ],
            'this_month' => [
                'total_orders' => Order::whereBetween('ordered_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
                'total_revenue' => Order::whereBetween('ordered_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('total_amount'),
            ],
            'status_breakdown' => Order::selectRaw('status, COUNT(*) as count')
                ->today()
                ->groupBy('status')
                ->pluck('count', 'status'),
            'order_type_breakdown' => Order::selectRaw('order_type, COUNT(*) as count')
                ->today()
                ->groupBy('order_type')
                ->pluck('count', 'order_type'),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    /**
     * Sync orders (for real-time updates)
     */
    public function sync(Request $request)
    {
        $lastSync = $request->get('last_sync');

        $query = Order::with(['user', 'table', 'items.product']);

        if ($lastSync) {
            $query->where('updated_at', '>', Carbon::parse($lastSync));
        }

        $orders = $query->orderBy('updated_at', 'desc')->limit(50)->get();

        return response()->json([
            'status' => 'success',
            'data' => OrderResource::collection($orders),
            'sync_timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Remove the specified order
     */
    public function destroy(Order $order)
    {
        if (!in_array($order->status, ['pending', 'cancelled'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only delete pending or cancelled orders'
            ], Response::HTTP_BAD_REQUEST);
        }

        return DB::transaction(function () use ($order) {
            // Restore stock if order was confirmed
            if ($order->status === 'pending') {
                foreach ($order->items as $item) {
                    if ($item->product->track_stock) {
                        $item->product->increment('stock_quantity', $item->quantity);
                    }
                }
            }

            // Free up table if applicable
            if ($order->table_id && $order->order_type === 'dine_in') {
                Table::where('id', $order->table_id)->update(['status' => 'available']);
            }

            $order->delete();
            $this->clearOrderCache($order->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Order deleted successfully'
            ]);
        });
    }

    /**
     * Bulk update orders with automatic payment status
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'orders' => 'required|array|max:100', // Limit bulk operations
            'orders.*.id' => 'required|exists:orders,id',
            'orders.*.status' => 'nullable|in:pending,confirmed,preparing,ready,served,completed,cancelled',
            'orders.*.payment_status' => 'nullable|in:pending,paid,partial,refunded',
            'orders.*.payment_method' => 'nullable|in:cash,card,digital_wallet,bank_transfer',
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
            $orderIds = collect($request->orders)->pluck('id')->toArray();

            // Bulk load orders to reduce queries
            $orders = Order::whereIn('id', $orderIds)->get()->keyBy('id');

            foreach ($request->orders as $orderData) {
                $order = $orders->get($orderData['id']);
                if ($order) {
                    $updateData = [];

                    // Handle status update
                    if (isset($orderData['status'])) {
                        $updateData['status'] = $orderData['status'];

                        // Set timestamps based on status
                        switch ($orderData['status']) {
                            case 'confirmed':
                                $updateData['confirmed_at'] = now();
                                break;
                            case 'preparing':
                                $updateData['confirmed_at'] = $order->confirmed_at ?? now();
                                break;
                            case 'ready':
                                $updateData['ready_at'] = now();
                                break;
                            case 'served':
                                $updateData['served_at'] = now();
                                break;
                            case 'completed':
                                $updateData['completed_at'] = now();
                                // Free up table if dine-in
                                if ($order->table_id && $order->order_type === 'dine_in') {
                                    Table::where('id', $order->table_id)->update(['status' => 'available']);
                                }
                                break;
                            case 'cancelled':
                                $updateData['cancelled_at'] = now();
                                // Free up table if dine-in
                                if ($order->table_id && $order->order_type === 'dine_in') {
                                    Table::where('id', $order->table_id)->update(['status' => 'available']);
                                }
                                break;
                        }
                    }

                    // Automatically mark all updated orders as paid
                    // This implements the requirement that all transactions should be auto-marked as paid
                    $updateData['payment_status'] = 'paid';
                    $updateData['paid_amount'] = $order->total_amount;

                    // Set default payment method if not provided
                    if (isset($orderData['payment_method'])) {
                        $updateData['payment_method'] = $orderData['payment_method'];
                    } elseif (empty($order->payment_method)) {
                        $updateData['payment_method'] = 'cash';
                    }

                    if (!empty($updateData)) {
                        $order->update($updateData);
                        $updated++;
                    }
                }
            }

            $this->clearOrderCache();

            return response()->json([
                'status' => 'success',
                'message' => "Updated {$updated} orders successfully and automatically marked as paid",
                'data' => [
                    'updated_count' => $updated
                ]
            ]);
        });
    }

    /**
     * Clear order-related caches
     */
    private function clearOrderCache($orderId = null)
    {
        // Cache clearing logic removed since we're not using caching
    }
}
