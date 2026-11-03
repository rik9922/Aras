<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\User;
use App\Services\QuickBooksService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateQuickBooksCustomer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public array $clientData
    ) {}

    public function handle(): void
    {
        $syncHash = hash('sha256', json_encode($this->normalizedClientData()));

        // Mark attempt on the Client profile
        Client::whereHas('user', fn ($q) => $q->where('id', $this->userId))
            ->update([
                'qb_customer_sync_status'          => 'syncing',
                'qb_customer_last_sync_attempt_at' => now(),
                'qb_customer_last_sync_error'      => null,
            ]);

        try {
            $user    = User::find($this->userId);
            $profile = $user?->clientProfile;
            $reservedId = $profile?->quickbooks_customer_id ? (string) $profile->quickbooks_customer_id : null;

            if (! $user || ! $profile || ! $profile->hasRequiredQuickBooksCustomerData()) {
                Client::whereHas('user', fn ($q) => $q->where('id', $this->userId))
                    ->update([
                        'qb_customer_sync_status'     => null,
                        'qb_customer_last_sync_error' => null,
                    ]);

                Log::info('Skipping QuickBooks customer sync because Bill To profile is incomplete', ['user_id' => $this->userId]);

                return;
            }

            $quickBooksService = new QuickBooksService();

            if ($reservedId) {
                $updated = $quickBooksService->updateCustomer($reservedId, $this->clientData);

                Client::whereHas('user', fn ($q) => $q->where('id', $this->userId))
                    ->update($updated ? [
                        'qb_customer_sync_status'          => 'synced',
                        'qb_customer_synced_at'            => now(),
                        'qb_customer_last_sync_error'      => null,
                        'qb_customer_sync_hash'            => $syncHash,
                    ] : [
                        'qb_customer_sync_status'     => 'failed',
                        'qb_customer_last_sync_error' => 'QuickBooks customer update failed. Check logs for details.',
                    ]);

                return;
            }

            $qbCustomerId = $quickBooksService->createCustomer($this->clientData);

            if ($qbCustomerId) {
                $qbCustomerId = (string) $qbCustomerId;

                Client::whereHas('user', fn ($q) => $q->where('id', $this->userId))
                    ->update([
                        'quickbooks_customer_id'           => $qbCustomerId,
                        'qb_customer_sync_status'          => 'synced',
                        'qb_customer_synced_at'            => now(),
                        'qb_customer_last_sync_error'      => null,
                        'qb_customer_sync_hash'            => $syncHash,
                    ]);

                Log::info('QuickBooks customer created via job', ['user_id' => $this->userId, 'qb_customer_id' => $qbCustomerId]);
            } else {
                Client::whereHas('user', fn ($q) => $q->where('id', $this->userId))
                    ->update([
                        'qb_customer_sync_status'     => 'failed',
                        'qb_customer_last_sync_error' => 'QuickBooks customer creation returned no id (not connected or API error).',
                    ]);
            }
        } catch (\Exception $e) {
            Client::whereHas('user', fn ($q) => $q->where('id', $this->userId))
                ->update([
                    'qb_customer_sync_status'     => 'failed',
                    'qb_customer_last_sync_error' => $e->getMessage(),
                ]);
            Log::error('CreateQuickBooksCustomer job failed: '.$e->getMessage(), ['user_id' => $this->userId]);
        }
    }

    private function normalizedClientData(): array
    {
        $data = $this->clientData;
        ksort($data);

        return $data;
    }
}
