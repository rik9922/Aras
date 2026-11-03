<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceMonthNotificationMark extends Model
{
    protected $table = 'invoice_month_notification_marks';

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
        'triggered_at' => 'datetime',
    ];
}

