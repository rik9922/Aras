<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
        'active' => 'boolean',
        'opening_balance' => 'decimal:2',
        'as_of_date' => 'date',
    ];

    /**
     * QuickBooks Purchase API expects: Cash, Check, CreditCard, Other.
     * Prefer account_type from QuickBooks (Bank -> Check, Credit Card -> CreditCard).
     */
    public function getQuickBooksPaymentTypeAttribute(): string
    {
        $accountType = strtolower((string) ($this->account_type ?? ''));
        if ($accountType === 'credit card' || $accountType === 'creditcard') {
            return 'CreditCard';
        }
        if ($accountType === 'bank') {
            return 'Check';
        }

        return match (strtolower($this->code ?? '')) {
            'check' => 'Check',
            'credit_card' => 'CreditCard',
            'cash' => 'Cash',
            'bank' => 'Other',
            default => 'Check',
        };
    }

    /**
     * QuickBooks Account Id to use for Purchase AccountRef (payment account).
     */
    public function getQuickBooksAccountIdForExpense(): ?string
    {
        if ($this->quickbooks_account_id) {
            return (string) $this->quickbooks_account_id;
        }

        return null;
    }

    public static function getDefault(): ?self
    {
        $default = static::where('is_default', true)->where('active', true)->first();

        return $default ?? static::where('active', true)->first();
    }
}
