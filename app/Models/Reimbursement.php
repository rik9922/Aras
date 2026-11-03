<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reimbursement extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function fundingSource(): BelongsTo
    {
        return $this->belongsTo(DwollaFundingSource::class, 'dwolla_funding_source_id');
    }

    public function getDescriptionForQuickBooksAttribute(): string
    {
        $monthYear = $this->expense_date ? $this->expense_date->format('F Y') : now()->format('F Y');
        $eventId = $this->event_id ? " - Event ID {$this->event_id}" : '';

        return "{$monthYear} Reimbursement{$eventId}";
    }
}
