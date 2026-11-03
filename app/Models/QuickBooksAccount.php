<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickBooksAccount extends Model
{
    protected $table = "quickbooks_accounts";
    protected $guarded = [];

    /**
     * Find the best Income account from the locally synced Chart of Accounts.
     * Prefers ServiceFeeIncome sub-type; falls back to any active Income account.
     */
    public static function getIncomeAccount(): ?self
    {
        return static::where('is_active', true)
            ->where('account_type', 'Income')
            ->orderByRaw("CASE WHEN account_sub_type = 'ServiceFeeIncome' THEN 0 ELSE 1 END")
            ->first();
    }

    /**
     * Find the best Expense account from the locally synced Chart of Accounts.
     * Prefers CostOfLabor sub-type; falls back to any active Expense account.
     */
    public static function getExpenseAccount(): ?self
    {
        return static::where('is_active', true)
            ->where('account_type', 'Expense')
            ->orderByRaw("CASE WHEN account_sub_type = 'CostOfLabor' THEN 0 ELSE 1 END")
            ->first();
    }

    /**
     * Find the best Bank account from the locally synced Chart of Accounts.
     * Prefers Checking sub-type; falls back to any active Bank account.
     */
    public static function getBankAccount(): ?self
    {
        return static::where('is_active', true)
            ->where('account_type', 'Bank')
            ->orderByRaw("CASE WHEN account_sub_type = 'Checking' THEN 0 ELSE 1 END")
            ->first();
    }
}
