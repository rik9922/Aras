<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceEventLink extends Model
{
    protected $table = 'invoice_event_links';

    protected $guarded = [];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}

