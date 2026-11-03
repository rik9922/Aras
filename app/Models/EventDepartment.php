<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventDepartment extends Model
{
    protected $table ='event_departments';
    protected $fillable = [
        'name',
        'description',
    ];

    public function events()
    {
        return $this->hasMany(Event::class, 'department_id');
    }
}
