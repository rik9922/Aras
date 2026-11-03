<?php

namespace App\Services\Dwolla;

use App\Models\AmbassadorPayout;
use App\Services\Dwolla\TransferService;

class PayoutRetryService
{
  protected TransferService $transferService;

  public function __construct(TransferService $transferService)
  {
    $this->transferService = $transferService;
  }

  public function retryFailedPayouts()
  {
    $failedPayouts = AmbassadorPayout::where('status', 'failed')
      ->where('retry_count', '<', 3)->get();

    foreach ($failedPayouts as $payout) {
      try {
        // Increment retry count
        $payout->increment('retry_count');
        $payout->update(['last_retry_at' => now()]);

        // Retry transfer via TransferService
        $this->transferService->create([
          'user_id' => $payout->user_id,
          'funding_source_id' => $payout->dwolla_funding_source_id,
          'amount' => $payout->amount,
          'source_funding_source' => config('services.dwolla.platform_funding_source'), // Platform bank
          'destination_funding_source' => $payout->fundingSource->dwolla_funding_source_id,
        ]);

      } catch (\Exception $e) {
        \Log::error("Retry failed for payout {$payout->id}: " . $e->getMessage());
      }
    }
  }
}