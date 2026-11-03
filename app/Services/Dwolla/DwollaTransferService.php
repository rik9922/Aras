<?php

namespace App\Services\Dwolla;

use App\Models\AmbassadorPayout;
use App\Models\Reimbursement;
use Illuminate\Support\Facades\Log;

class DwollaTransferService
{
    protected $client;

    public function __construct()
    {
        $this->client = new DwollaClient;
    }

    public function createForReimbursement(Reimbursement $reimbursement): string
    {
        $user = $reimbursement->user;
        $fundingSource = $user->dwollaCustomer?->ensureVerifiedDefaultFundingSource();

        if (! $fundingSource) {
            throw new \Exception('User has no verified bank account for reimbursement.');
        }

        $environment = config('services.dwolla.environment');
        $baseUrl = $environment === 'sandbox' ? 'https://api-sandbox.dwolla.com' : 'https://api.dwolla.com';
        $sourceId = config('services.dwolla.platform_funding_source');
        $destinationId = $fundingSource->dwolla_funding_source_id;
        $sourceHref = "{$baseUrl}/funding-sources/{$sourceId}";
        $destinationHref = "{$baseUrl}/funding-sources/{$destinationId}";

        $reference = 'REIMB-'.$reimbursement->id.'-'.uniqid();

        $payload = [
            '_links' => [
                'source' => ['href' => $sourceHref],
                'destination' => ['href' => $destinationHref],
            ],
            'amount' => [
                'currency' => 'USD',
                'value' => number_format($reimbursement->amount, 2, '.', ''),
            ],
            'metadata' => [
                'payout_reference' => $reference,
            ],
        ];

        Log::channel('reimbursement_pay')->info('Sending reimbursement transfer request to Dwolla.', [
            'reimbursement_id' => $reimbursement->id,
            'user_id' => $reimbursement->user_id,
            'environment' => $environment,
            'source_funding_source_suffix' => $sourceId ? substr($sourceId, -8) : null,
            'destination_funding_source_id' => $fundingSource->id,
            'destination_funding_source_suffix' => $destinationId ? substr($destinationId, -8) : null,
            'currency' => $payload['amount']['currency'],
            'amount' => $payload['amount']['value'],
            'payout_reference' => $reference,
        ]);

        $response = $this->client->post('/transfers', $payload);
        if (! $response->successful()) {
            Log::channel('reimbursement_pay')->error('Dwolla reimbursement transfer request failed.', [
                'reimbursement_id' => $reimbursement->id,
                'user_id' => $reimbursement->user_id,
                'http_status' => $response->status(),
                'response_body' => substr($response->body(), 0, 2000),
            ]);

            throw new \Exception('Dwolla reimbursement transfer failed: '.$response->body());
        }
        $location = $response->header('Location');

        $transferId = basename($location);

        Log::channel('reimbursement_pay')->info('Dwolla reimbursement transfer request succeeded.', [
            'reimbursement_id' => $reimbursement->id,
            'user_id' => $reimbursement->user_id,
            'http_status' => $response->status(),
            'dwolla_transfer_id' => $transferId,
        ]);

        return $transferId;
    }

    public function create(AmbassadorPayout $payout): string
    {
        $isExpedited = (float) $payout->expedited_fee > 0;
        $environment = config('services.dwolla.environment');
        $baseUrl = $environment === 'sandbox' ? 'https://api-sandbox.dwolla.com' : 'https://api.dwolla.com';
        $sourceId = config('services.dwolla.platform_funding_source');
        $fundingSource = $payout->fundingSource;

        if (! $fundingSource) {
            if ($isExpedited) {
                Log::channel('expedited_pay')->error('Dwolla transfer request stopped: payout has no funding source relation.', [
                    'payout_id' => $payout->id,
                    'event_id' => $payout->event_id,
                    'ambassador_id' => $payout->user_id,
                    'dwolla_funding_source_id' => $payout->dwolla_funding_source_id,
                ]);
            }

            throw new \Exception('Payout has no Dwolla funding source.');
        }

        $destinationId = $fundingSource->dwolla_funding_source_id;
        $sourceHref = "{$baseUrl}/funding-sources/{$sourceId}";
        $destinationHref = "{$baseUrl}/funding-sources/{$destinationId}";

        $payload = [
            '_links' => [
                'source' => [
                    'href' => $sourceHref,
                ],
                'destination' => [
                    'href' => $destinationHref,
                ],
            ],
            'amount' => [
                'currency' => $payout->currency,
                'value' => number_format($payout->amount, 2, '.', ''),
            ],
            'metadata' => [
                'payout_reference' => $payout->reference,
            ],
        ];

        if ($isExpedited) {
            Log::channel('expedited_pay')->info('Sending expedited payout transfer request to Dwolla.', [
                'payout_id' => $payout->id,
                'event_id' => $payout->event_id,
                'ambassador_id' => $payout->user_id,
                'environment' => $environment,
                'source_funding_source_suffix' => $sourceId ? substr($sourceId, -8) : null,
                'destination_funding_source_id' => $fundingSource->id,
                'destination_funding_source_suffix' => $destinationId ? substr($destinationId, -8) : null,
                'currency' => $payload['amount']['currency'],
                'amount' => $payload['amount']['value'],
                'payout_reference' => $payout->reference,
            ]);
        } else {
            Log::info('Payload :', [$payload]);
        }

        $response = $this->client->post('/transfers', $payload);
        if (! $response->successful()) {
            if ($isExpedited) {
                Log::channel('expedited_pay')->error('Dwolla transfer request failed for expedited payout.', [
                    'payout_id' => $payout->id,
                    'event_id' => $payout->event_id,
                    'ambassador_id' => $payout->user_id,
                    'http_status' => $response->status(),
                    'response_body' => substr($response->body(), 0, 2000),
                ]);
            }

            throw new \Exception('Dwolla transfer creation failed: '.$response->body());
        }
        $location = $response->header('Location');
        $transferId = basename($location);

        if ($isExpedited) {
            Log::channel('expedited_pay')->info('Dwolla transfer request succeeded for expedited payout.', [
                'payout_id' => $payout->id,
                'event_id' => $payout->event_id,
                'ambassador_id' => $payout->user_id,
                'http_status' => $response->status(),
                'dwolla_transfer_id' => $transferId,
            ]);
        }

        return $transferId;
    }
}
