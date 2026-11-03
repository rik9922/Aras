<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DwollaBalanceAlertSnapshot extends Model
{
    protected $guarded = [];

    protected $casts = [
        'window_start' => 'datetime',
        'window_end' => 'datetime',
        'is_breach' => 'boolean',
        'notified_at' => 'datetime',
        'meta' => 'array',
    ];
}
