<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recap extends Model
{
    protected $table = 'recaps';
    protected $guarded = [];

    protected $casts = [
        'is_approved'              => 'boolean',
        'is_draft'                 => 'boolean',
        'is_expedited_requested'   => 'boolean',
        'expedited_requested_at'   => 'datetime',
        'thc_products_sold'        => 'array',
        'backup_brand_used'        => 'boolean',
        'buyback_amount'           => 'decimal:2',
    ];

    /**
     * Copy recap buyback to the parent event for invoicing / QuickBooks.
     */
    public function syncBuybackToEvent(): void
    {
        $event = $this->event;
        if (! $event) {
            return;
        }

        $event->update([
            'buyback_amount' => (float) ($this->buyback_amount ?? 0),
        ]);
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_selling_id', 'id');
    }

    public function location()
    {
        return $this->belongsTo(User::class, 'ambassador_id', 'id');
    }

    public function ambassador()
    {
        return $this->belongsTo(User::class, 'ambassador_id', 'id');
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id', 'id');
    }

    public function dailySummaryMark()
    {
        return $this->hasOne(RecapDailySummaryMark::class, 'recap_id', 'id');
    }
}
