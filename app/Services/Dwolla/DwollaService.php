<?php

namespace App\Services\Dwolla;

class DwollaService
{
  protected $client;
  public function __construct()
  {
    $this->client = new DwollaClient();
  }

  public function verifyMicroDeposits(string $fundingSourceId, array $amounts)
  {
    return $this->client->post(
      "/funding-sources/{$fundingSourceId}/micro-deposits",
      $amounts
    );
  }

  public function setAsDefault(string $fundingSourceId)
  {
    return $this->client->post("/funding-sources/{$fundingSourceId}", [
      'metadata' => [
        'is_default' => true
      ]
    ]);
  }


}