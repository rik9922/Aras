<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Client profile — represents the extended client record in the `clients` table.
 *
 * Every client is a User (role_id = 3). This model holds the client-specific data
 * (QB sync, billing defaults, address, budget limits) linked via user_id FK.
 * Access via $user->clientProfile or User::where('role_id', 3)->with('clientProfile').
 */
class Client extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'default_labor_amount'             => 'decimal:2',
            'default_buyback_amount'           => 'decimal:2',
            'default_cups_amount'              => 'decimal:2',
            'budget_max_amount'                => 'decimal:2',
            'budget_enforce'                   => 'boolean',
            'budget_require_approval'          => 'boolean',
            'qb_customer_last_sync_attempt_at' => 'datetime',
            'qb_customer_synced_at'            => 'datetime',
            'cc_emails'                        => 'array',
        ];
    }

    public function quickBooksCustomerData(): array
    {
        $user = $this->user;
        $name = trim((string) ($user?->name ?? ''));
        if ($name === '') {
            $name = trim(($user?->first_name ?? '').' '.($user?->last_name ?? ''));
        }

        return [
            'name'         => $name,
            'company_name' => $this->company_name ?: $name,
            'email'        => $user?->email,
            'phone'        => $user?->phone,
            'address'      => $this->address,
            'cc_emails'    => $this->cc_emails ?? [],
        ];
    }

    public function hasRequiredQuickBooksCustomerData(): bool
    {
        $data = $this->quickBooksCustomerData();

        return trim((string) ($data['name'] ?? '')) !== ''
            && filter_var($data['email'] ?? null, FILTER_VALIDATE_EMAIL)
            && trim((string) ($data['phone'] ?? '')) !== ''
            && trim((string) ($data['address'] ?? '')) !== '';
    }

    public function quickBooksCustomerSyncHash(): string
    {
        $data = $this->quickBooksCustomerData();
        ksort($data);

        return hash('sha256', json_encode($data));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Parent Bill To account whose shared billing details flow to this child. */
    public function parentBillTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_bill_to_id');
    }

    /** Child Bill To profiles grouped under this profile's user. */
    public function childBillTos()
    {
        return $this->hasMany(self::class, 'parent_bill_to_id', 'user_id');
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'client_brand')
            ->withTimestamps();
    }

    public function distributors(): BelongsToMany
    {
        return $this->belongsToMany(EventDistributor::class, 'client_distributor', 'client_id', 'distributor_id')
            ->withTimestamps();
    }
}
