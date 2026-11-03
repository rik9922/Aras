<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmbassadorFirstShiftManagerAlert extends Model
{
    protected $table = 'em_ba_first_shift_alerts';

    protected $fillable = [
        'ambassador_id',
        'first_shift_event_id',
        'first_shift_at',
        'market_manager_id',
        'notified_at',
        'suppressed_reason',
        'suppressed_at',
    ];

    protected function casts(): array
    {
        return [
            'first_shift_at' => 'datetime',
            'notified_at' => 'datetime',
            'suppressed_at' => 'datetime',
        ];
    }

    public function ambassador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ambassador_id', 'id');
    }

    public function firstShiftEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'first_shift_event_id', 'id');
    }

    public function marketManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'market_manager_id', 'id');
    }
}
