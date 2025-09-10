<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'table_number' => $this->table_number,
            'capacity' => $this->capacity,
            'status' => $this->status,
            'area' => $this->area,
            'description' => $this->description,
            'position_x' => $this->position_x,
            'position_y' => $this->position_y,
            'is_active' => $this->is_active,
            'status_changed_at' => $this->status_changed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'current_order_id' => $this->current_order_id,
            
            // Current orders (if loaded)
            'current_orders' => $this->when($this->relationLoaded('orders'), function () {
                return OrderResource::collection($this->orders->where('status', '!=', 'completed'));
            }),
            
            // All orders (if loaded)
            'orders' => $this->when($this->relationLoaded('orders') && !$this->orders->isEmpty(), function () {
                return OrderResource::collection($this->orders);
            }),
            
            // Quick stats
            'stats' => $this->when($this->relationLoaded('orders'), function () {
                $orders = $this->orders;
                return [
                    'total_orders' => $orders->count(),
                    'active_orders' => $orders->where('status', '!=', 'completed')->count(),
                    'total_revenue' => $orders->sum('total_amount'),
                    'avg_order_value' => $orders->avg('total_amount'),
                ];
            }),
            
            // Metadata
            'metadata' => $this->metadata,
        ];
    }
}