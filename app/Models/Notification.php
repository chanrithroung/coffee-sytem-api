<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Events\NotificationSent;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'message',
        'data',
        'read',
        'user_id',
        'related_type',
        'related_id',
        'priority',
        'expires_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read' => 'boolean',
        'expires_at' => 'datetime',
    ];

    protected $dates = [
        'expires_at',
    ];

    protected $dispatchesEvents = [
        'created' => NotificationSent::class,
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'high' => 'text-red-600',
            'medium' => 'text-yellow-600',
            'low' => 'text-blue-600',
            default => 'text-gray-600',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'order' => 'shopping-cart',
            'stock' => 'package',
            'system' => 'settings',
            'payment' => 'credit-card',
            'table' => 'table',
            'user' => 'user',
            'error' => 'alert-triangle',
            'success' => 'check-circle',
            'warning' => 'alert-circle',
            'info' => 'info',
            default => 'bell',
        };
    }
}
