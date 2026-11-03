<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ambassador extends Model
{
    protected $fillable = [
        'user_id',
        'nickname',
        'dob',
        'venmo',
        'region',
        'timezone',
        'titos_trained',
        'thc_quiz_passed',
        'male_ba_lgbtq_ok',
        'attended_trainings',
        'tips_certified',
        'has_permit',
        'legal_address',
        'legal_city',
        'legal_state',
        'legal_zip_code',
        'same_as_legal',
        'mailing_address',
        'mailing_city',
        'mailing_state',
        'mailing_zip_code',
        'latitude',
        'longitude',
        'favorite_coffee_shop',
        'favorite_restaurant',
        'favorite_place_to_shop',
        'favorite_charity',
        'hired',
        'wage',
        'shirt_size',
        // market_id removed — markets stored in ambassador_market pivot, not ambassadors table
        'dwolla_customer_id',
        'verification_status',
    ];

    protected function casts(): array
    {
        return [
            'dob'               => 'date',
            'hired'             => 'date',
            'titos_trained'     => 'boolean',
            'thc_quiz_passed'   => 'boolean',
            'male_ba_lgbtq_ok'  => 'boolean',
            'attended_trainings'=> 'boolean',
            'tips_certified'    => 'boolean',
            'has_permit'        => 'boolean',
            'same_as_legal'     => 'boolean',
            'wage'              => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Many-to-many: Ambassador belongs to many Markets via pivot table ambassador_market.
     */
    public function markets()
    {
        return $this->belongsToMany(Market::class, 'ambassador_market');
    }

    // Legacy: market_id comma-separated accessor kept for backward compatibility
    // while the codebase migrates reads to the pivot. New writes use the pivot.
    public function getMarketIdAttribute($value): array
    {
        // Prefer pivot markets if loaded
        if ($this->relationLoaded('markets')) {
            return $this->getRelation('markets')->pluck('id')->toArray();
        }
        return $value ? array_map('intval', explode(',', $value)) : [];
    }
}
