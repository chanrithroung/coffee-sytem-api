<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'product_id',
        'name',
        'description',
        'price',
        'sale_price',
        'is_on_sale',
        'is_available',
        'is_visible',
        'is_featured',
        'sort_order',
        'tags',
        'allergens',
        'nutrition_info',
        'preparation_time',
        'customizations',
        'images',
    ];

    protected $casts = [
        'is_on_sale' => 'boolean',
        'is_available' => 'boolean',
        'is_visible' => 'boolean',
        'is_featured' => 'boolean',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'tags' => 'array',
        'allergens' => 'array',
        'nutrition_info' => 'array',
        'customizations' => 'array',
        'images' => 'array',
        'preparation_time' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOnSale($query)
    {
        return $query->where('is_on_sale', true);
    }
}
