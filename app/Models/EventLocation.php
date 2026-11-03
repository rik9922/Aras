<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventLocation extends Model
{
    protected $table='event_locations';
    protected $guarded =[];

    public function event(){
        return $this->belongsTo(Event::class,'event_id','id');
    }

    public function brand(){
        return $this->belongsTo(Brand::class,'brand_selling_id','id');
    }

    public function location(){
        return $this->belongsTo(User::class,'ambassador_id','id');
    }

    public function ambassador(){
        return $this->belongsTo(User::class,'ambassador_id','id');
    }

    public function state(){
        return $this->belongsTo(State::class,'state_id','id');
    }
}
