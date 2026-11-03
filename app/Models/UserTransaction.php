<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserTransaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'occurred_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function getTypeLabelAttribute(): string
    {
        return match (true) {
            $this->source_type === AmbassadorPayout::class,
            $this->type === 'payout',
            $this->type === 'event_payout' => 'Event Pay',

            $this->source_type === Reimbursement::class,
            $this->type === 'reimbursement' => 'Reimbursement',

            default => ucfirst(str_replace('_', ' ', (string) $this->type)),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'completed', 'paid' => 'Paid',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function getBankNameAttribute(): ?string
    {
        if (! empty($this->meta['bank_name'])) {
            return $this->meta['bank_name'];
        }

        if ($this->source instanceof AmbassadorPayout) {
            return $this->source->fundingSource?->bank_name;
        }

        if ($this->source instanceof Reimbursement) {
            return $this->source->fundingSource?->bank_name;
        }

        return null;
    }

    public function getBankAccountLast4Attribute(): ?string
    {
        if (! empty($this->meta['account_last4'])) {
            return $this->meta['account_last4'];
        }

        if ($this->source instanceof AmbassadorPayout) {
            $accountNumber = $this->source->fundingSource?->account_number;

            return $accountNumber ? substr((string) $accountNumber, -4) : null;
        }

        if ($this->source instanceof Reimbursement) {
            $accountNumber = $this->source->fundingSource?->account_number;

            return $accountNumber ? substr((string) $accountNumber, -4) : null;
        }

        return null;
    }
}
