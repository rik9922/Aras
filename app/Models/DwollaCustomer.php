<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DwollaCustomer extends Model
{
    protected $guarded = [];

    // Dwolla Customer → User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Dwolla Customer → Funding Sources

    public function defaultFundingSource(): HasOne
    {
        return $this->hasOne(DwollaFundingSource::class)
            ->where('is_default', 1)
            ->where('status', 'verified');
    }

    /** Default funding source that is verified (required for payouts/reimbursements). */
    public function verifiedDefaultFundingSource(): HasOne
    {
        return $this->hasOne(DwollaFundingSource::class)
            ->where('is_default', 1)
            ->where('status', 'verified');
    }

    public function verifiedFundingSources(): HasMany
    {
        return $this->hasMany(DwollaFundingSource::class)->where('status', 'verified');
    }

    public function ensureVerifiedDefaultFundingSource(): ?DwollaFundingSource
    {
        $default = $this->verifiedDefaultFundingSource()->first();
        if ($default) {
            $this->fundingSources()
                ->where('id', '!=', $default->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            return $default;
        }

        $source = $this->verifiedFundingSources()
            ->orderByDesc('verified_at')
            ->orderBy('id')
            ->first();

        if (! $source) {
            $this->fundingSources()
                ->where('is_default', true)
                ->update(['is_default' => false]);

            return null;
        }

        $this->fundingSources()->update(['is_default' => false]);
        $source->update(['is_default' => true]);

        return $source->fresh();
    }

    public function fundingSources(): HasMany
    {
        return $this->hasMany(DwollaFundingSource::class)->where('status', '!=', 'removed');
    }
}
