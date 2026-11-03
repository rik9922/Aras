<?php

namespace App\Services\Dwolla;

use App\Models\DwollaCustomer;
use App\Models\DwollaFundingSource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FundingSourceService
{
  public function create(
    DwollaCustomer $customer,
    array $bankData,
    bool $setAsDefault = false
  ): DwollaFundingSource {

    DB::beginTransaction();

    try {
      $client = new DwollaClient();

      $response = $client->post(
        "/customers/{$customer->dwolla_customer_id}/funding-sources",
        $bankData
      );

      // ❗ Dwolla validation error
      if ($response->failed()) {
        throw new \Exception(
          $this->extractDwollaError($response)
        );
      }

      $location = $response->header('Location');
      $fundingSourceId = basename($location);

      $sourceData = [
        'dwolla_customer_id' => $customer->id,
        'dwolla_funding_source_id' => $fundingSourceId,
        'bank_name' => $bankData['name'],
        'account_type' => $bankData['bankAccountType'],
        'account_number' => $bankData['accountNumber'],
        'status' => 'unverified',
        'is_default' => false,
        'raw_response' => $response->json(),
      ];

      $source = DwollaFundingSource::where('dwolla_funding_source_id', $fundingSourceId)->first();

      if ($source) {
        if ($source->dwolla_customer_id !== $customer->id || $source->status !== 'removed') {
          throw new \Exception('Bank already exists');
        }

        $source->update($sourceData);
      } else {
        $source = DwollaFundingSource::create($sourceData);
      }

      // Newly added bank accounts must be verified before they can become default.
      if ($setAsDefault && $source->status === 'verified') {
        DwollaFundingSource::where('dwolla_customer_id', $customer->id)
          ->update(['is_default' => false]);

        $source->update(['is_default' => true]);
      }

      DB::commit();
      return $source;

    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function deactivate(DwollaFundingSource $source): void
  {
    DB::beginTransaction();

    try {
      $client = new DwollaClient();

      // Deactivate funding source on Dwolla
      $response = $client->post(
        "/funding-sources/{$source->dwolla_funding_source_id}",
        ['removed' => true]
      );

      if ($response->failed()) {
        throw new \Exception('Unable to deactivate funding source on Dwolla');
      }

      // Soft delete the funding source locally
      $source->update([
        'status' => 'removed',
        'is_default' => false, // also unset default flag
      ]);

      // Reassign default bank if the deactivated one was default
      $nextDefault = DwollaFundingSource::where('dwolla_customer_id', $source->dwolla_customer_id)
        ->where('status', 'verified')
        ->where('id', '!=', $source->id)
        ->first();

      if ($nextDefault) {
        $nextDefault->update(['is_default' => true]);
      }

      DB::commit();

    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }



  public function verifyMicroDeposits(string $fundingSourceId, $amount1, $amount2)
  {
    $client = new DwollaClient();
    $response = $client->post("/funding-sources/{$fundingSourceId}/micro-deposits", [
      'amount1' => number_format($amount1, 2, '.', ''),
      'amount2' => number_format($amount2, 2, '.', ''),
    ]);

    if ($response->failed()) {
      throw new \Exception(
        $response->json('message') ?? 'Micro-deposit verification failed'
      );
    }

    return true;
  }

  private function extractDwollaError($response): string
  {
    $json = $response->json();

    if (isset($json['_embedded']['errors'])) {
      return collect($json['_embedded']['errors'])
        ->pluck('message')
        ->implode(', ');
    }

    return $json['message'] ?? 'Dwolla validation failed';
  }

}
