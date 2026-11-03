<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachement extends Model
{
    protected $table = 'attachements';
    protected $fillable = ['event_id','filename','type','driver'];
}
