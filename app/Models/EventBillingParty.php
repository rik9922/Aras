<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventBillingParty extends Model
{
    protected $table = 'event_billing_parties';

    protected $guarded = [];

    protected $casts = [
        'is_primary' => 'boolean',
        'labor_rate' => 'decimal:2',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id', 'id');
    }
}
