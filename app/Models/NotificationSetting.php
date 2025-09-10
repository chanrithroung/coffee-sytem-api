<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'enabled',
        'show_desktop',
        'show_in_app',
        'sound',
        'types'
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'show_desktop' => 'boolean',
        'show_in_app' => 'boolean',
        'sound' => 'boolean',
        'types' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
