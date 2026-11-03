<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class W9Form extends Model
{
    protected $guarded = [];

    protected $casts = [
        'signature_date' => 'date',
        'is_ein' => 'boolean',
        'is_1099_vendor' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
