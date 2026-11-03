<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandCategory extends Model
{
    protected $fillable = [
        'name',
        'notes'
    ];

    public function brands()
    {
        return $this->belongsToMany(
            Brand::class,
            'brand_brand_category',
            'brand_category_id',
            'brand_id'
        )->withTimestamps();
    }
}
