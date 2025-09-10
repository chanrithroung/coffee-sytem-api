<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_number',
        'name',
        'capacity',
        'status',
        'location',
        'position_x',
        'position_y',
        'qr_code',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'position_x' => 'decimal:2',
        'position_y' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected $appends = [
        'current_order',
        'is_occupied',
    ];

    // Auto-generate QR code
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->qr_code)) {
                $model->qr_code = 'TABLE-' . strtoupper(Str::random(10));
            }
        });
    }

    // Relationships
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function currentOrder()
    {
        return $this->hasOne(Order::class)
            ->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready'])
            ->latest()
            ->first();
    }

    public function activeOrders(): HasMany
    {
        return $this->hasMany(Order::class)
            ->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready']);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied');
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location', $location);
    }

    public function scopeByCapacity($query, $minCapacity, $maxCapacity = null)
    {
        $query = $query->where('capacity', '>=', $minCapacity);
        
        if ($maxCapacity) {
            $query = $query->where('capacity', '<=', $maxCapacity);
        }
        
        return $query;
    }

    // Accessors
    public function getCurrentOrderAttribute()
    {
        return $this->currentOrder();
    }

    public function getCurrentOrderIdAttribute()
    {
        return $this->currentOrder()?->id;
    }

    public function getIsOccupiedAttribute()
    {
        return $this->status === 'occupied';
    }

    // Methods
    public function occupy()
    {
        $this->update(['status' => 'occupied']);
        return $this;
    }

    public function makeAvailable()
    {
        $this->update(['status' => 'available']);
        return $this;
    }

    public function reserve()
    {
        $this->update(['status' => 'reserved']);
        return $this;
    }

    public function setMaintenance()
    {
        $this->update(['status' => 'maintenance']);
        return $this;
    }

    public function canAccommodate($partySize)
    {
        return $this->capacity >= $partySize && $this->status === 'available';
    }

    public function hasActiveOrders()
    {
        return $this->activeOrders()->exists();
    }
}
