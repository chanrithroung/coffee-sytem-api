<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Http\Resources\TableResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TableController extends Controller
{
    /**
     * Display a listing of tables
     */
    public function index(Request $request)
    {
        $query = Table::query();

        // Apply filters
        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);

        // Only load relationships if specifically requested (performance optimization)
        if ($request->boolean('include_current_order')) {
            $this->applyRelationships($query, $request);
        }

        // Get all tables or paginate
        if ($request->boolean('paginate', true)) {
            $perPage = min($request->get('per_page', 15), 100);
            $tables = $query->paginate($perPage);
            $responseData = TableResource::collection($tables)->response()->getData();
        } else {
            $tables = $query->get();
            $responseData = TableResource::collection($tables);
        }

        return [
            'status' => 'success',
            'data' => $responseData,
            'meta' => [
                'total_tables' => Table::count(),
                'available_tables' => Table::where('status', 'available')->count(),
                'occupied_tables' => Table::where('status', 'occupied')->count(),
                'reserved_tables' => Table::where('status', 'reserved')->count(),
            ]
        ];
    }

    private function applyFilters($query, Request $request)
    {
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('area')) {
            $query->where('area', $request->get('area'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('capacity_min')) {
            $query->where('capacity', '>=', $request->get('capacity_min'));
        }

        if ($request->has('capacity_max')) {
            $query->where('capacity', '<=', $request->get('capacity_max'));
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('table_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('area', 'like', "%{$search}%");
            });
        }
    }

    private function applySorting($query, Request $request)
    {
        $sortBy = $request->get('sort_by', 'table_number');
        $sortOrder = $request->get('sort_order', 'asc');

        $allowedSortColumns = ['id', 'table_number', 'capacity', 'status', 'area', 'created_at'];

        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('table_number', 'asc');
        }
    }

    private function applyRelationships($query, Request $request)
    {
        $with = [];

        if ($request->boolean('include_current_order')) {
            $with['orders'] = function ($q) {
                $q->active()->latest()->with('items.product:id,name');
            };
        }

        if (!empty($with)) {
            $query->with($with);
        }
    }

    /**
     * Store a newly created table
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table_number' => 'required|string|max:10|unique:tables,table_number',
            'capacity' => 'required|integer|min:1|max:20',
            'area' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
            'position_x' => 'nullable|numeric',
            'position_y' => 'nullable|numeric',
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

        $data = $validator->validated();
        $data['status'] = 'available';
        $data['is_active'] = $data['is_active'] ?? true;

        $table = Table::create($data);
        $this->clearTableCache();

        return response()->json([
            'status' => 'success',
            'message' => 'Table created successfully',
            'data' => new TableResource($table)
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified table
     */
    public function show(Request $request, Table $table)
    {
        $with = [];

        if ($request->boolean('include_orders', true)) {
            $with['orders'] = function ($q) {
                $q->with(['items.product:id,name,price', 'user:id,name'])
                    ->orderBy('ordered_at', 'desc')
                    ->limit(10);
            };
        }

        if (!empty($with)) {
            $table->load($with);
        }

        return response()->json([
            'status' => 'success',
            'data' => new TableResource($table)
        ]);
    }

    /**
     * Update the specified table
     */
    public function update(Request $request, Table $table)
    {
        $validator = Validator::make($request->all(), [
            'table_number' => 'sometimes|required|string|max:10|unique:tables,table_number,' . $table->id,
            'capacity' => 'sometimes|required|integer|min:1|max:20',
            'area' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
            'position_x' => 'nullable|numeric',
            'position_y' => 'nullable|numeric',
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

        $table->update($validator->validated());
        $this->clearTableCache($table->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Table updated successfully',
            'data' => new TableResource($table->fresh())
        ]);
    }

    /**
     * Update table status
     */
    public function updateStatus(Request $request, Table $table)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:available,occupied,reserved,cleaning,out_of_order',
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
        $oldStatus = $table->status;

        // Business logic validation
        if ($table->status === 'occupied' && $newStatus === 'available') {
            // Check if there are active orders for this table
            $activeOrders = $table->orders()->active()->get();
            if ($activeOrders->count() > 0) {
                // Check if user has permission to override (admin or specific role)
                $user = $request->user();
                $canOverride = $user && (
                    $user->hasRole('admin') ||
                    $user->hasRole('manager') ||
                    $user->hasRole('sale')
                );

                if (!$canOverride) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot make table available while it has active orders',
                        'data' => ['active_orders' => $activeOrders->count()]
                    ], Response::HTTP_BAD_REQUEST);
                }

                // For authorized users, auto-complete active orders to free up the table
                foreach ($activeOrders as $order) {
                    $order->complete();
                }
            }
        }

        $table->update([
            'status' => $newStatus,
            'status_changed_at' => now(),
        ]);

        $this->clearTableCache($table->id);

        // Prepare response message
        $message = "Table status updated from {$oldStatus} to {$newStatus}";
        if ($oldStatus === 'occupied' && $newStatus === 'available' && isset($activeOrders) && $activeOrders->count() > 0) {
            $message .= ". {$activeOrders->count()} active order(s) were automatically completed.";
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => new TableResource($table->fresh())
        ]);
    }

    /**
     * Get available tables
     */
    public function available(Request $request)
    {
        $query = Table::where('status', 'available')->where('is_active', true);

        if ($request->has('capacity_min')) {
            $query->where('capacity', '>=', $request->get('capacity_min'));
        }

        if ($request->has('area')) {
            $query->where('area', $request->get('area'));
        }

        $data = $query->orderBy('table_number')->get();

        return response()->json([
            'status' => 'success',
            'data' => TableResource::collection($data),
            'meta' => [
                'count' => $data->count(),
                'areas' => $data->pluck('area')->unique()->filter()->values()
            ]
        ]);
    }

    /**
     * Bulk update tables
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tables' => 'required|array|max:50',
            'tables.*.id' => 'required|exists:tables,id',
            'tables.*.status' => 'nullable|in:available,occupied,reserved,cleaning,out_of_order',
            'tables.*.is_active' => 'nullable|boolean',
            'tables.*.area' => 'nullable|string|max:50',
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
            $tableIds = collect($request->tables)->pluck('id')->toArray();

            // Bulk load tables to reduce queries
            $tables = Table::whereIn('id', $tableIds)->get()->keyBy('id');

            foreach ($request->tables as $tableData) {
                $table = $tables->get($tableData['id']);
                if ($table) {
                    $updateData = array_filter([
                        'status' => $tableData['status'] ?? null,
                        'is_active' => isset($tableData['is_active']) ? $tableData['is_active'] : null,
                        'area' => $tableData['area'] ?? null,
                    ]);

                    if (!empty($updateData)) {
                        $table->update($updateData);
                        $updated++;
                    }
                }
            }

            $this->clearTableCache();

            return response()->json([
                'status' => 'success',
                'message' => "Updated {$updated} tables successfully",
                'data' => [
                    'updated_count' => $updated
                ]
            ]);
        });
    }

    /**
     * Get table statistics
     */
    public function stats(Request $request)
    {
        $totalTables = Table::count();
        $activeTables = Table::where('is_active', true)->count();

        $statusBreakdown = Table::selectRaw('status, COUNT(*) as count')
            ->where('is_active', true)
            ->groupBy('status')
            ->pluck('count', 'status');

        $areaBreakdown = Table::selectRaw('area, COUNT(*) as count')
            ->whereNotNull('area')
            ->where('is_active', true)
            ->groupBy('area')
            ->pluck('count', 'area');

        $capacityStats = Table::selectRaw('MIN(capacity) as min_capacity, MAX(capacity) as max_capacity, AVG(capacity) as avg_capacity, SUM(capacity) as total_capacity')
            ->where('is_active', true)
            ->first();

        $data = [
            'totals' => [
                'total_tables' => $totalTables,
                'active_tables' => $activeTables,
                'inactive_tables' => $totalTables - $activeTables,
            ],
            'status_breakdown' => $statusBreakdown,
            'area_breakdown' => $areaBreakdown,
            'capacity_stats' => [
                'min_capacity' => (int) $capacityStats->min_capacity,
                'max_capacity' => (int) $capacityStats->max_capacity,
                'avg_capacity' => round($capacityStats->avg_capacity, 1),
                'total_capacity' => (int) $capacityStats->total_capacity,
            ],
            'utilization' => [
                'occupied_rate' => $activeTables > 0 ? round(($statusBreakdown['occupied'] ?? 0) / $activeTables * 100, 1) : 0,
                'available_rate' => $activeTables > 0 ? round(($statusBreakdown['available'] ?? 0) / $activeTables * 100, 1) : 0,
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    /**
     * Sync tables (for real-time updates)
     */
    public function sync(Request $request)
    {
        $lastSync = $request->get('last_sync');

        $query = Table::query();

        if ($lastSync) {
            $query->where('updated_at', '>', Carbon::parse($lastSync));
        }

        $tables = $query->orderBy('updated_at', 'desc')->limit(50)->get();

        return response()->json([
            'status' => 'success',
            'data' => TableResource::collection($tables),
            'sync_timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Remove the specified table
     */
    public function destroy(Table $table)
    {
        // Check if table has any orders
        $ordersCount = $table->orders()->count();
        if ($ordersCount > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete table with existing orders',
                'data' => ['orders_count' => $ordersCount]
            ], Response::HTTP_CONFLICT);
        }

        // Check if table is currently occupied
        if ($table->status === 'occupied') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete occupied table'
            ], Response::HTTP_BAD_REQUEST);
        }

        $table->delete();
        $this->clearTableCache($table->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Table deleted successfully'
        ]);
    }

    /**
     * Clear table-related caches
     */
    private function clearTableCache($tableId = null)
    {
        // Cache clearing logic removed since we're not using caching
    }
}
