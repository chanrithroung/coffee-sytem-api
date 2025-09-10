<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'sku',
        'barcode',
        'price',
        'cost',
        'unit',
        'images',
        'is_active',
        'preparation_time',
        'variants',
        'nutrition_info',
        'allergens',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'preparation_time' => 'integer',
        'is_active' => 'boolean',
        'images' => 'array',
        'variants' => 'array',
        'nutrition_info' => 'array',
        'allergens' => 'array',
        'metadata' => 'array',
    ];

    protected $appends = [
        'profit_margin',
        'main_image',
    ];

    // Auto-generate slug and SKU
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
            if (empty($model->sku)) {
                $model->sku = 'PRD-' . strtoupper(Str::random(8));
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name') && empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Accessors
    public function getProfitMarginAttribute()
    {
        if (!$this->cost || $this->cost == 0) {
            return null;
        }
        return round((($this->price - $this->cost) / $this->cost) * 100, 2);
    }

    public function getMainImageAttribute()
    {
        if (is_array($this->images) && count($this->images) > 0) {
            return $this->images[0];
        }
        return null;
    }
}
