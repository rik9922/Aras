<?php

namespace App\Trait;

use App\Helper\QuickBooksHelper;
use App\Jobs\CreateQuickBooksCustomer;
use App\Models\User;
use Illuminate\Support\Facades\Log;

trait EnsuresQuickBooksCustomer
{
    public function ensureQuickBooksCustomer($user)
    {
        $this->queueQuickBooksCustomerSyncIfReady($user);
    }

    protected function queueQuickBooksCustomerSyncIfReady(?User $user, bool $force = false): bool
    {
        if (! $user || (int) $user->role_id !== 3 || ! QuickBooksHelper::isConnected()) {
            return false;
        }

        $user->loadMissing('clientProfile');
        $profile = $user->clientProfile;

        if (! $profile || ! $profile->hasRequiredQuickBooksCustomerData()) {
            Log::info('Skipping automatic QuickBooks customer sync: Bill To profile is incomplete', [
                'user_id' => $user->id,
            ]);

            return false;
        }

        $syncHash = $profile->quickBooksCustomerSyncHash();
        $status = (string) $profile->qb_customer_sync_status;
        $hashMatches = hash_equals((string) $profile->qb_customer_sync_hash, $syncHash);

        if ($hashMatches && in_array($status, ['queued', 'syncing'], true)) {
            return false;
        }

        if (! $force && $hashMatches && $status === 'synced') {
            return false;
        }

        $profile->update([
            'qb_customer_sync_status'     => 'queued',
            'qb_customer_last_sync_error' => null,
            'qb_customer_sync_hash'       => $syncHash,
        ]);

        CreateQuickBooksCustomer::dispatch($user->id, $profile->quickBooksCustomerData());

        return true;
    }
}
