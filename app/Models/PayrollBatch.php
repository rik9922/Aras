<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollBatch extends Model
{
    protected $guarded = [];

    protected $casts = [
        'period_start'  => 'date',
        'period_end'    => 'date',
        'generated_at'  => 'datetime',
        'processed_at'  => 'datetime',
        'total_records' => 'integer',
        'total_amount'  => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PayrollBatchItem::class, 'batch_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
