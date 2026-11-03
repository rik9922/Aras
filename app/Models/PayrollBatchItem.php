<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollBatchItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount'       => 'decimal:2',
        'is_expedited' => 'boolean',
        'processed_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PayrollBatch::class, 'batch_id');
    }

    public function ambassador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ambassador_id');
    }

    /**
     * The recap this item was generated from (payment_type = 'recap').
     * reference_id stores the recap primary key.
     */
    public function recap(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Recap::class, 'reference_id');
    }
}
