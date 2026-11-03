<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecapDailySummaryMark extends Model
{
    protected $table = 'recap_daily_summary_marks';

    protected $guarded = [];

    protected $casts = [
        'last_notified_updated_at' => 'datetime',
        'summary_date' => 'date',
    ];

    public function recap()
    {
        return $this->belongsTo(Recap::class, 'recap_id', 'id');
    }
}
