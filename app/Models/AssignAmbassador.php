<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignAmbassador extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_no_show' => 'boolean',
        'is_at_event_location' => 'boolean',
        'forced_check_in' => 'boolean',
        'late_checkin_notification_sent' => 'boolean',
        'late_checkin_notification_sent_at' => 'datetime',
        'check_in_ready_notification_sent' => 'boolean',
        'check_in_ready_notification_sent_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    public function ambassador()
    {
        return $this->belongsTo(User::class, 'ambassador_id', 'id');
    }

    public function payout()
    {
        return $this->hasOne(AmbassadorPayout::class, 'event_id', 'event_id');
    }
}
