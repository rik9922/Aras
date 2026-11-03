<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceLineItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity'   => 'decimal:4',
        'rate'       => 'decimal:2',
        'amount'     => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
