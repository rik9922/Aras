<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AmbassadorPayout extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'expedited_fee' => 'decimal:2',
        'approved_at' => 'datetime',
        'queued_at' => 'datetime',
        'processing_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_retry_at' => 'datetime',

    ];

    // Payout → Ambassador

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Payout → Funding Source
    public function fundingSource(): BelongsTo
    {
        return $this->belongsTo(DwollaFundingSource::class, 'dwolla_funding_source_id');
    }

    // Payout → Invoice (legacy usage; stores remote ID)
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // Payout → QuickBooks Expense (local copy via expenses table)
    public function expense(): HasOne
    {
        return $this->hasOne(Expense::class, 'payout_id');
    }
}
