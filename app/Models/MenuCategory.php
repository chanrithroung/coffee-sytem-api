<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color',
        'sort_order',
        'is_active',
        'is_visible',
        'available_from',
        'available_to',
        'available_days',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
        'available_days' => 'array',
        'available_from' => 'datetime',
        'available_to' => 'datetime',
        'sort_order' => 'integer',
    ];

    // Set default value for available_days at the model level
    protected $attributes = [
        'available_days' => '[0,1,2,3,4,5,6]',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }
}
