<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_sku',
        'unit_price',
        'quantity',
        'line_total',
        'customizations',
        'special_instructions',
        'status',
        'preparation_time',
        'confirmed_at',
        'started_at',
        'ready_at',
        'served_at',
        'metadata',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'quantity' => 'integer',
        'preparation_time' => 'integer',
        'customizations' => 'array',
        'metadata' => 'array',
        'confirmed_at' => 'datetime',
        'started_at' => 'datetime',
        'ready_at' => 'datetime',
        'served_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_customizations',
        'is_ready',
        'preparation_status',
    ];

    // Auto-calculate line total
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->line_total = $model->unit_price * $model->quantity;
        });

        static::created(function ($model) {
            // Take snapshot of product details
            if ($model->product) {
                $model->update([
                    'product_name' => $model->product->name,
                    'product_sku' => $model->product->sku,
                    'preparation_time' => $model->product->preparation_time,
                ]);
            }
        });
    }

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePreparing($query)
    {
        return $query->where('status', 'preparing');
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    public function scopeServed($query)
    {
        return $query->where('status', 'served');
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    // Accessors
    public function getFormattedCustomizationsAttribute()
    {
        if (!$this->customizations || !is_array($this->customizations)) {
            return null;
        }

        $formatted = [];
        foreach ($this->customizations as $key => $value) {
            $formatted[] = ucfirst($key) . ': ' . $value;
        }

        return implode(', ', $formatted);
    }

    public function getIsReadyAttribute()
    {
        return $this->status === 'ready';
    }

    public function getPreparationStatusAttribute()
    {
        switch ($this->status) {
            case 'pending':
                return 'Waiting to start';
            case 'confirmed':
                return 'Confirmed';
            case 'preparing':
                return 'In preparation';
            case 'ready':
                return 'Ready to serve';
            case 'served':
                return 'Served';
            case 'cancelled':
                return 'Cancelled';
            default:
                return 'Unknown';
        }
    }

    // Status methods
    public function confirm()
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return $this;
    }

    public function startPreparing()
    {
        $this->update([
            'status' => 'preparing',
            'started_at' => now(),
        ]);

        return $this;
    }

    public function markReady()
    {
        $this->update([
            'status' => 'ready',
            'ready_at' => now(),
        ]);

        return $this;
    }

    public function serve()
    {
        $this->update([
            'status' => 'served',
            'served_at' => now(),
        ]);

        return $this;
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);

        return $this;
    }

    // Helper methods
    public function getTotalPreparationTime()
    {
        return ($this->preparation_time ?? 0) * $this->quantity;
    }

    public function canBeModified()
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    public function updateQuantity($newQuantity)
    {
        if (!$this->canBeModified()) {
            return false;
        }

        $this->update([
            'quantity' => $newQuantity,
            'line_total' => $this->unit_price * $newQuantity,
        ]);

        return true;
    }
}
