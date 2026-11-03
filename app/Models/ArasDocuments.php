<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArasDocuments extends Model
{
    protected $table = 'aras_documents';
    protected $fillable = ['user_id','file_name','url','file_size'];

    public function ambassador() {
        return $this->belongsTo(User::class,'user_id','id');     
    }
}
