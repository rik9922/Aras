<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'role_id' => 'integer',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            // budget_*, default_*, quickbooks_* are NOT cast here — those fields
            // live in the `clients` table and are accessed via clientProfile.
            // Casting them on User would shadow the proxy accessors below.
        ];
    }

    /***************************** [START] JWT methods :- ***************************/

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
    /***************************** [END] JWT methods :- ***************************/

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * The Client profile row for this user (role_id = 3 only).
     * Named clientProfile to avoid collision with Event::client() / EventBillingParty::client().
     */
    public function clientProfile()
    {
        return $this->hasOne(Client::class);
    }

    /**
     * The Ambassador profile row for this user (role_id = 5 only).
     */
    public function ambassadorProfile()
    {
        return $this->hasOne(Ambassador::class);
    }

    public function getProfileImageAttribute()
    {
        // For ambassadors (role_id 5): prefer profile_image from ambassadors table
        $raw = $this->ambassadorProfile?->getRawOriginal('profile_image')
            ?? $this->attributes['profile_image']
            ?? null;

        return ! empty($raw) ? asset($raw) : null;
    }

    /** Raw profile image path (no asset() wrapping) — used by userProfileImageUrl() helper. */
    public function getRawProfileImagePath(): ?string
    {
        return $this->ambassadorProfile?->getRawOriginal('profile_image')
            ?? $this->attributes['profile_image']
            ?? null;
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    public function market()
    {
        return $this->belongsTo(Market::class, 'market_id', 'id');
    }

    public function getMarketIdAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }

        if (! empty($value)) {
            return explode(',', $value);
        }

        return [];
    }

    public function getBrandIdAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }

        if (! empty($value)) {
            return explode(',', $value);
        }

        return [];
    }

    public function getDistributorIdAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }

        if (! empty($value)) {
            return explode(',', $value);
        }

        return [];
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'id', 'user_id');
    }

    public function location_state()
    {
        return $this->hasOne(EventLocation::class, 'id', 'event_location_id');
    }

    public function assignedEvents()
    {
        return $this->belongsToMany(Event::class, 'assign_ambassadors', 'ambassador_id', 'event_id');
    }

    public function lastEvent()
    {
        return $this->assignedEvents()->orderBy('end_time', 'desc');
    }

    // Ambassador → Dwolla Customer
    public function dwollaCustomer()
    {
        return $this->hasOne(DwollaCustomer::class);
    }

    /** Whether the user has a verified bank account (required for reimbursements and payouts). */
    public function hasVerifiedBankAccount(): bool
    {
        $customer = $this->dwollaCustomer;

        return $customer && $customer->verifiedFundingSources()->exists();
    }

    /**
     * Bank setup readiness: not_started | pending_verification | verified
     */
    public function getBankSetupStatus(): string
    {
        if ($this->hasVerifiedBankAccount()) {
            return 'verified';
        }

        $customer = $this->dwollaCustomer;
        if (! $customer) {
            return 'not_started';
        }

        if ($customer->relationLoaded('fundingSources')) {
            return $customer->fundingSources->isNotEmpty() ? 'pending_verification' : 'not_started';
        }

        return $customer->fundingSources()->exists() ? 'pending_verification' : 'not_started';
    }

    public function getBankSetupStatusLabel(): string
    {
        return match ($this->getBankSetupStatus()) {
            'verified' => 'Verified',
            'pending_verification' => 'Pending Verification',
            default => 'Not Started',
        };
    }

    public function scopeBankSetupStatus($query, string $status)
    {
        return match ($status) {
            'verified' => $query->whereHas('dwollaCustomer.verifiedFundingSources'),
            'pending_verification' => $query->whereDoesntHave('dwollaCustomer.verifiedFundingSources')
                ->whereHas('dwollaCustomer.fundingSources'),
            'not_started' => $query->whereDoesntHave('dwollaCustomer.verifiedFundingSources')
                ->where(function ($q) {
                    $q->whereDoesntHave('dwollaCustomer')
                        ->orWhereHas('dwollaCustomer', fn ($sub) => $sub->whereDoesntHave('fundingSources'));
                }),
            default => $query,
        };
    }

    // Ambassador → Payouts
    public function ambassadorPayouts()
    {
        return $this->hasMany(AmbassadorPayout::class);
    }

    public function w9Form()
    {
        return $this->hasOne(W9Form::class);
    }

    public function ambassadorLiquorLicense()
    {
        return $this->hasOne(AmbassadorLiquorLicense::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Ambassador profile proxy accessors
    //
    // Ambassador-specific fields live in the `ambassadors` table via the
    // ambassadorProfile relationship. These transparent accessors let existing
    // views/callers use $user->dob, $user->wage, etc. without any changes.
    // ─────────────────────────────────────────────────────────────────────────

    public function getDobAttribute()
    {
        return $this->ambassadorProfile?->dob ?? $this->attributes['dob'] ?? null;
    }

    public function getVenmoAttribute(): ?string
    {
        return $this->ambassadorProfile?->venmo ?? $this->attributes['venmo'] ?? null;
    }

    public function getNicknameAttribute(): ?string
    {
        return $this->ambassadorProfile?->nickname ?? $this->attributes['nickname'] ?? null;
    }

    public function getRegionAttribute(): ?string
    {
        return $this->ambassadorProfile?->region ?? $this->attributes['region'] ?? null;
    }

    public function getWageAttribute()
    {
        return $this->ambassadorProfile?->wage ?? $this->attributes['wage'] ?? null;
    }

    public function getHiredAttribute()
    {
        return $this->ambassadorProfile?->hired ?? $this->attributes['hired'] ?? null;
    }

    public function getShirtSizeAttribute(): ?string
    {
        return $this->ambassadorProfile?->shirt_size ?? $this->attributes['shirt_size'] ?? null;
    }

    public function getLatitudeAttribute(): ?string
    {
        return $this->ambassadorProfile?->latitude ?? $this->attributes['latitude'] ?? null;
    }

    public function getLongitudeAttribute(): ?string
    {
        return $this->ambassadorProfile?->longitude ?? $this->attributes['longitude'] ?? null;
    }

    public function getLegalAddressAttribute(): ?string
    {
        return $this->ambassadorProfile?->legal_address ?? $this->attributes['legal_address'] ?? null;
    }

    public function getLegalCityAttribute(): ?string
    {
        return $this->ambassadorProfile?->legal_city ?? $this->attributes['legal_city'] ?? null;
    }

    public function getLegalStateIdAttribute()
    {
        return $this->ambassadorProfile?->legal_state ?? $this->attributes['legal_state_id'] ?? null;
    }

    /** legal_state short code (NY, CA) — reads from ambassadors.legal_state */
    public function getLegalStateAttribute(): ?string
    {
        return $this->ambassadorProfile?->legal_state ?? $this->attributes['legal_state'] ?? null;
    }

    public function getLegalZipCodeAttribute(): ?string
    {
        return $this->ambassadorProfile?->legal_zip_code ?? $this->attributes['legal_zip_code'] ?? null;
    }

    public function getSameAsLegalAttribute(): bool
    {
        return (bool) ($this->ambassadorProfile?->same_as_legal ?? $this->attributes['same_as_legal'] ?? false);
    }

    public function getMailingAddressAttribute(): ?string
    {
        return $this->ambassadorProfile?->mailing_address ?? $this->attributes['mailing_address'] ?? null;
    }

    public function getMailingCityAttribute(): ?string
    {
        return $this->ambassadorProfile?->mailing_city ?? $this->attributes['mailing_city'] ?? null;
    }

    public function getMailingStateIdAttribute()
    {
        return $this->ambassadorProfile?->mailing_state ?? $this->attributes['mailing_state_id'] ?? null;
    }

    /** mailing_state short code (NY, CA) — reads from ambassadors.mailing_state */
    public function getMailingStateAttribute(): ?string
    {
        return $this->ambassadorProfile?->mailing_state ?? $this->attributes['mailing_state'] ?? null;
    }

    public function getMailingZipCodeAttribute(): ?string
    {
        return $this->ambassadorProfile?->mailing_zip_code ?? $this->attributes['mailing_zip_code'] ?? null;
    }

    public function getTitosTrained(): bool
    {
        return (bool) ($this->ambassadorProfile?->titos_trained ?? $this->attributes['titos_trained'] ?? false);
    }

    public function getThcQuizPassedAttribute(): bool
    {
        return (bool) ($this->ambassadorProfile?->thc_quiz_passed ?? $this->attributes['thc_quiz_passed'] ?? false);
    }

    public function getMaleBaLgbtqOkAttribute(): bool
    {
        return (bool) ($this->ambassadorProfile?->male_ba_lgbtq_ok ?? $this->attributes['male_ba_lgbtq_ok'] ?? false);
    }

    public function getAttendedTrainingsAttribute(): bool
    {
        return (bool) ($this->ambassadorProfile?->attended_trainings ?? $this->attributes['attended_trainings'] ?? false);
    }

    public function getTipsCertifiedAttribute(): bool
    {
        return (bool) ($this->ambassadorProfile?->tips_certified ?? $this->attributes['tips_certified'] ?? false);
    }

    public function getHasPermitAttribute(): bool
    {
        return (bool) ($this->ambassadorProfile?->has_permit ?? $this->attributes['has_permit'] ?? false);
    }

    public function getFavoriteCoffeeShopAttribute(): ?string
    {
        return $this->ambassadorProfile?->favorite_coffee_shop ?? $this->attributes['favorite_coffee_shop'] ?? null;
    }

    public function getFavoriteRestaurantAttribute(): ?string
    {
        return $this->ambassadorProfile?->favorite_restaurant ?? $this->attributes['favorite_restaurant'] ?? null;
    }

    public function getFavoritePlaceToShopAttribute(): ?string
    {
        return $this->ambassadorProfile?->favorite_place_to_shop ?? $this->attributes['favorite_place_to_shop'] ?? null;
    }

    public function getFavoriteCharityAttribute(): ?string
    {
        return $this->ambassadorProfile?->favorite_charity ?? $this->attributes['favorite_charity'] ?? null;
    }

    /** Markets a POS user (role_id 7) is allowed to view. */
    public function posMarkets()
    {
        return $this->belongsToMany(Market::class, 'market_user');
    }

    /** Markets a Manager user (role_id 6) is assigned to. */
    public function managerMarkets()
    {
        return $this->belongsToMany(Market::class, 'manager_market_user');
    }

    /** Events assigned to a Manager user (role_id 6). */
    public function managedEvents()
    {
        return $this->hasMany(Event::class, 'manager_id');
    }

    /** One-time first-shift reminder state for the Event Market Manager (2 days before first shift). */
    public function ambassadorFirstShiftManagerAlert()
    {
        return $this->hasOne(AmbassadorFirstShiftManagerAlert::class, 'ambassador_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Client profile proxy accessors
    //
    // All client-specific fields live in the `clients` table via the
    // clientProfile relationship. These transparent accessors let existing
    // views/callers use $user->quickbooks_customer_id (and related fields)
    // without any changes — the value is read from clientProfile automatically.
    //
    // IMPORTANT: getStateAttribute is intentionally omitted because Laravel
    // uses the same name for the state() BelongsTo relationship accessor.
    // The view-client blade reads state directly via $client->clientProfile->state.
    // ─────────────────────────────────────────────────────────────────────────

    /** QB customer ID — reads from clients.quickbooks_customer_id */
    public function getQuickbooksCustomerIdAttribute(): ?string
    {
        return $this->clientProfile?->quickbooks_customer_id;
    }

    /** QB sync status — reads from clients.qb_customer_sync_status */
    public function getQbCustomerSyncStatusAttribute(): ?string
    {
        return $this->clientProfile?->qb_customer_sync_status;
    }

    /** QB last sync attempt timestamp — reads from clients.qb_customer_last_sync_attempt_at */
    public function getQbCustomerLastSyncAttemptAtAttribute()
    {
        return $this->clientProfile?->qb_customer_last_sync_attempt_at;
    }

    /** QB synced-at timestamp — reads from clients.qb_customer_synced_at */
    public function getQbCustomerSyncedAtAttribute()
    {
        return $this->clientProfile?->qb_customer_synced_at;
    }

    /** QB last sync error — reads from clients.qb_customer_last_sync_error */
    public function getQbCustomerLastSyncErrorAttribute(): ?string
    {
        return $this->clientProfile?->qb_customer_last_sync_error;
    }

    // ── Address / billing / budget proxies ────────────────────────────────────

    public function getAddressAttribute(): ?string
    {
        return $this->clientProfile?->address ?? $this->attributes['address'] ?? null;
    }

    public function getCityAttribute(): ?string
    {
        return $this->clientProfile?->city;
    }

    // Note: getStateAttribute intentionally omitted — conflicts with state() relationship.
    // Use $user->clientProfile->state directly in views/code that need the client's state.

    public function getZipCodeAttribute(): ?string
    {
        return $this->clientProfile?->zip_code ?? $this->attributes['zip_code'] ?? null;
    }

    public function getCountryAttribute(): ?string
    {
        return $this->clientProfile?->country;
    }

    public function getCompanyNameAttribute(): ?string
    {
        return $this->clientProfile?->company_name;
    }

    public function getPrimaryBillingContactAttribute(): ?string
    {
        return $this->clientProfile?->primary_billing_contact;
    }

    public function getNotesAttribute(): ?string
    {
        return $this->clientProfile?->notes;
    }

    public function getContractRecievedAttribute()
    {
        return $this->clientProfile?->contract_recieved;
    }

    public function getDefaultLaborAmountAttribute()
    {
        return $this->clientProfile?->default_labor_amount;
    }

    public function getDefaultBuybackAmountAttribute()
    {
        return $this->clientProfile?->default_buyback_amount;
    }

    public function getDefaultCupsAmountAttribute()
    {
        return $this->clientProfile?->default_cups_amount;
    }

    // ── Budget proxies ────────────────────────────────────────────────────────

    public function getBudgetProgramNameAttribute(): ?string
    {
        return $this->clientProfile?->budget_program_name;
    }

    public function getBudgetMaxAmountAttribute()
    {
        return $this->clientProfile?->budget_max_amount;
    }

    public function getBudgetMaxEventsAttribute()
    {
        return $this->clientProfile?->budget_max_events;
    }

    public function getBudgetWarnPercentAttribute()
    {
        return $this->clientProfile?->budget_warn_percent ?? 80;
    }

    public function getBudgetEnforceAttribute(): bool
    {
        return (bool) ($this->clientProfile?->budget_enforce ?? false);
    }

    public function getBudgetRequireApprovalAttribute(): bool
    {
        return (bool) ($this->clientProfile?->budget_require_approval ?? false);
    }

    public function getBudgetAlertEmailAttribute(): ?string
    {
        return $this->clientProfile?->budget_alert_email;
    }
}
