<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'description' => $this->when($this->description, $this->description),
            'color' => $this->color,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'metadata' => $this->when($this->metadata, $this->metadata),
            
            // Conditional relationships
            'products_count' => $this->when(
                $this->relationLoaded('products'), 
                fn() => $this->products->count()
            ),
            'active_products_count' => $this->when(
                $this->relationLoaded('activeProducts'), 
                fn() => $this->activeProducts->count()
            ),
            'products' => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}