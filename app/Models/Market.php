<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Ambassador;
use App\Models\EventType;

class Market extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'markets';
    protected $guarded = [];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function posUsers()
    {
        return $this->belongsToMany(User::class, 'market_user');
    }

    public function managers()
    {
        return $this->belongsToMany(User::class, 'manager_market_user');
    }

    public function eventTypes()
    {
        return $this->belongsToMany(EventType::class, 'market_event_type');
    }

    /**
     * Many-to-many: Market has many Ambassadors via pivot table ambassador_market.
     */
    public function ambassadors()
    {
        return $this->belongsToMany(Ambassador::class, 'ambassador_market');
    }
}
