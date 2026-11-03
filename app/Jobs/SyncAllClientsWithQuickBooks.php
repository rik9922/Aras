<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAllClientsWithQuickBooks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        try {
            // Find clients whose Client profile has no quickbooks_customer_id yet
            $clients = User::where('role_id', 3)
                ->whereHas('clientProfile', fn ($q) => $q->whereNull('quickbooks_customer_id')->orWhere('quickbooks_customer_id', ''))
                ->with('clientProfile')
                ->get();

            Log::info('Starting QuickBooks sync for '.$clients->count().' clients');

            foreach ($clients as $client) {
                $profile = $client->clientProfile;
                if (! $profile || ! $profile->hasRequiredQuickBooksCustomerData()) {
                    continue;
                }

                $syncHash = $profile->quickBooksCustomerSyncHash();
                if (in_array($profile->qb_customer_sync_status, ['queued', 'syncing'], true)
                    && hash_equals((string) $profile->qb_customer_sync_hash, $syncHash)) {
                    continue;
                }

                $profile->update([
                    'qb_customer_sync_status'     => 'queued',
                    'qb_customer_last_sync_error' => null,
                    'qb_customer_sync_hash'       => $syncHash,
                ]);

                CreateQuickBooksCustomer::dispatch($client->id, $profile->quickBooksCustomerData())
                    ->delay(now()->addSeconds(rand(1, 5)));
            }

            Log::info('QuickBooks sync jobs dispatched successfully');
        } catch (\Exception $e) {
            Log::error('SyncAllClientsWithQuickBooks job failed: '.$e->getMessage());
        }
    }
}
