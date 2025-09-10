<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price' => $this->price,
            'cost' => $this->when($request->boolean('include_cost'), $this->cost),
            'profit_margin' => $this->when($request->boolean('include_profit'), $this->profit_margin),
            'profit_amount' => $this->when($request->boolean('include_profit'), $this->profit_amount),
            'stock_quantity' => $this->stock_quantity,
            'low_stock_threshold' => $this->low_stock_threshold,
            'is_low_stock' => $this->is_low_stock,
            'unit' => $this->unit,
            'images' => $this->images,
            'is_active' => $this->is_active,
            'track_stock' => $this->track_stock,
            'preparation_time' => $this->preparation_time,
            'description' => $this->when($request->boolean('include_description'), $this->description),
            'variants' => $this->when($request->boolean('include_variants'), $this->variants),
            'nutrition_info' => $this->when($request->boolean('include_nutrition'), $this->nutrition_info),
            'allergens' => $this->when($request->boolean('include_allergens'), $this->allergens),
            'metadata' => $this->when($request->boolean('include_metadata'), $this->metadata),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'category' => $this->when($this->relationLoaded('category'), function () {
                return new CategoryResource($this->category);
            }),
            'category_id' => $this->category_id,
            'category_name' => $this->when($this->relationLoaded('category'), $this->category?->name),
            
            // Counts and aggregates
            'order_items_count' => $this->when($this->relationLoaded('orderItemsCount'), $this->order_items_count),
            'total_sold' => $this->when($request->boolean('include_sales_stats'), $this->total_sold ?? 0),
            'revenue_generated' => $this->when($request->boolean('include_sales_stats'), $this->revenue_generated ?? 0),
            
            // Order history (limited)
            'recent_orders' => $this->when(
                $this->relationLoaded('orderItems') && $request->boolean('include_order_history'),
                OrderItemResource::collection($this->orderItems)
            ),
            
            // Computed fields
            'status' => $this->is_active ? 'active' : 'inactive',
            'stock_status' => $this->stock_quantity <= 0 ? 'out_of_stock' : ($this->stock_quantity <= $this->low_stock_threshold ? 'low_stock' : 'in_stock'),
        ];
    }
    
    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'timestamp' => now()->toISOString(),
                'includes' => $this->getActiveIncludes($request),
            ],
        ];
    }
    
    /**
     * Get list of active includes for this request
     */
    private function getActiveIncludes(Request $request): array
    {
        $includes = [];
        
        if ($request->boolean('include_cost')) $includes[] = 'cost';
        if ($request->boolean('include_profit')) $includes[] = 'profit';
        if ($request->boolean('include_description')) $includes[] = 'description';
        if ($request->boolean('include_variants')) $includes[] = 'variants';
        if ($request->boolean('include_nutrition')) $includes[] = 'nutrition';
        if ($request->boolean('include_allergens')) $includes[] = 'allergens';
        if ($request->boolean('include_metadata')) $includes[] = 'metadata';
        if ($request->boolean('include_sales_stats')) $includes[] = 'sales_stats';
        if ($request->boolean('include_order_history')) $includes[] = 'order_history';
        if ($this->relationLoaded('category')) $includes[] = 'category';
        
        return $includes;
    }
}