<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'table_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'order_type',
        'status',
        'payment_status',
        'payment_method',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'service_charge',
        'total_amount',
        'paid_amount',
        'notes',
        'special_instructions',
        'estimated_time',
        'ordered_at',
        'confirmed_at',
        'ready_at',
        'served_at',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'estimated_time' => 'integer',
        'ordered_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'ready_at' => 'datetime',
        'served_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'remaining_amount',
        'is_paid',
        'items_count',
        'estimated_ready_time',
    ];

    // Auto-generate order number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->order_number)) {
                $today = now()->format('Ymd');
                $count = static::whereDate('created_at', today())->count() + 1;
                $model->order_number = $today . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }

            if (empty($model->ordered_at)) {
                $model->ordered_at = now();
            }

            // Automatically set payment status to paid for completed orders
            // This ensures that when an order is created as completed, it's also marked as paid
            if ($model->status === 'completed' && empty($model->payment_status)) {
                $model->payment_status = 'paid';
                $model->paid_amount = $model->total_amount;
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['confirmed', 'preparing']);
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('order_type', $type);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTable($query, $tableId)
    {
        return $query->where('table_id', $tableId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('ordered_at', today());
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('ordered_at', [$startDate, $endDate]);
    }

    // Accessors
    public function getRemainingAmountAttribute()
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }

    public function getIsPaidAttribute()
    {
        return $this->remaining_amount <= 0;
    }

    public function getItemsCountAttribute()
    {
        return $this->items()->sum('quantity');
    }

    public function getEstimatedReadyTimeAttribute()
    {
        if (!$this->estimated_time || !$this->confirmed_at) {
            return null;
        }

        return $this->confirmed_at->addMinutes($this->estimated_time);
    }

    // Status methods
    public function confirm()
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'estimated_time' => $this->calculateEstimatedTime(),
        ]);

        return $this;
    }

    public function startPreparing()
    {
        $this->update(['status' => 'preparing']);
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

    public function markServed()
    {
        $this->update([
            'status' => 'served',
            'served_at' => now(),
        ]);

        return $this;
    }

    public function complete()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Free up table if it's a dine-in order
        if ($this->table_id && $this->order_type === 'dine_in') {
            \App\Models\Table::where('id', $this->table_id)->update(['status' => 'available']);
        }

        return $this;
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);

        // Free up table if applicable
        if ($this->table_id && $this->order_type === 'dine_in') {
            \App\Models\Table::where('id', $this->table_id)->update(['status' => 'available']);
        }

        return $this;
    }

    // Calculation methods
    public function calculateTotals()
    {
        $subtotal = $this->items()->sum('line_total');
        $taxRate = 0.10; // 10% tax
        $serviceChargeRate = 0.05; // 5% service charge for dine-in

        $taxAmount = $subtotal * $taxRate;
        $serviceCharge = ($this->order_type === 'dine_in') ? $subtotal * $serviceChargeRate : 0;
        $total = $subtotal + $taxAmount + $serviceCharge - $this->discount_amount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'service_charge' => $serviceCharge,
            'total_amount' => $total,
        ]);

        return $this;
    }

    public function calculateEstimatedTime()
    {
        $totalTime = $this->items()->join('products', 'order_items.product_id', '=', 'products.id')
            ->sum('products.preparation_time');

        return max(5, $totalTime); // Minimum 5 minutes
    }

    // Payment methods
    public function addPayment($amount, $method = 'cash')
    {
        $newPaidAmount = min($this->total_amount, $this->paid_amount + $amount);

        $this->update([
            'paid_amount' => $newPaidAmount,
            'payment_method' => $method,
            'payment_status' => $newPaidAmount >= $this->total_amount ? 'paid' : 'partial',
        ]);

        return $this;
    }
}
