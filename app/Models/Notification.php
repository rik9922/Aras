<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'title',
        'message',
        'type',
        'booking_id',
        'is_seen',
    ];

}
