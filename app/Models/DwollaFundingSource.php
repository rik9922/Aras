<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DwollaFundingSource extends Model
{
    protected $guarded = [];

    protected $casts = [
        'raw_response' => 'array',
        'is_default' => 'boolean',
    ];

    // Funding Source → Dwolla Customer
    public function dwollaCustomer(): BelongsTo
    {
        return $this->belongsTo(DwollaCustomer::class);
    }

    // Funding Source → Payouts
    public function payouts(): HasMany
    {
        return $this->hasMany(AmbassadorPayout::class);
    }
}
