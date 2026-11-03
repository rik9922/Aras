<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickBooksWebhookEvent extends Model
{
    protected $table = 'quickbooks_webhook_events';

    protected $guarded = [];

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'quickbooks_updated_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
