<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'order_type' => $this->order_type,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            
            // Customer information
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            
            // Financial details
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'service_charge' => $this->service_charge,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'remaining_amount' => $this->remaining_amount,
            'is_paid' => $this->is_paid,
            
            // Additional information
            'notes' => $this->notes,
            'special_instructions' => $this->special_instructions,
            'estimated_time' => $this->estimated_time,
            'items_count' => $this->items_count,
            
            // Timestamps
            'ordered_at' => $this->ordered_at,
            'confirmed_at' => $this->confirmed_at,
            'ready_at' => $this->ready_at,
            'served_at' => $this->served_at,
            'completed_at' => $this->completed_at,
            'estimated_ready_time' => $this->estimated_ready_time,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Related data (conditionally loaded)
            'user' => $this->when($this->relationLoaded('user'), function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            
            'table' => $this->when($this->relationLoaded('table'), function () {
                return [
                    'id' => $this->table->id,
                    'table_number' => $this->table->table_number,
                    'capacity' => $this->table->capacity,
                    'status' => $this->table->status,
                    'area' => $this->table->area,
                ];
            }),
            
            'items' => $this->when($this->relationLoaded('items'), function () {
                return OrderItemResource::collection($this->items);
            }),
            
            // Metadata
            'metadata' => $this->metadata,
        ];
    }
}