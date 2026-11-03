<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $table = 'notification_preferences';

    protected $fillable = [
        'user_id',
        'push_enabled',
        'email_enabled',
    ];

    protected $casts = [
        'push_enabled' => 'boolean',
        'email_enabled' => 'boolean',
    ];
}

