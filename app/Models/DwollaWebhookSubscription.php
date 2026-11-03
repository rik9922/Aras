<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DwollaWebhookSubscription extends Model
{
    protected $fillable = [
        'dwolla_subscription_id',
        'url',
        'paused',
        'dwolla_created_at',
    ];

    protected $casts = [
        'paused' => 'boolean',
        'dwolla_created_at' => 'datetime',
    ];
}
