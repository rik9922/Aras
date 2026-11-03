<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecapDraft extends Model
{
    protected $table = 'recap_drafts';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function ambassador()
    {
        return $this->belongsTo(User::class, 'ambassador_id');
    }
}
