<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InfoPage extends Model
{
    use HasFactory;

    protected $fillable = [
        "page_content",
        "page_url",
        "page_name",
        "plateform"
    ];
}
