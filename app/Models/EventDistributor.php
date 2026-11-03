<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventDistributor extends Model
{
    protected $table = 'event_distributors';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company_name',
        'address',
        'distributor_type',
    ];

    public function events()
    {
        return $this->hasMany(Event::class, 'distributor_id');
    }
}
