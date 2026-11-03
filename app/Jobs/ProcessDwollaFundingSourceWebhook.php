<?php

namespace App\Jobs;

use App\Models\DwollaCustomer;
use App\Models\DwollaFundingSource; // Assuming this model exists
use App\Models\DwollaWebhookEvent; // To link to customer
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDwollaFundingSourceWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $eventId;

    public function __construct($eventId)
    {
        $this->eventId = $eventId;
    }

    public function handle()
    {
        $event = DwollaWebhookEvent::find($this->eventId);
        if (! $event) {
            Log::error('Dwolla funding source webhook: event not found', ['event_id' => $this->eventId]);

            return;
        }

        $payload = $event->payload;
        $topic = $event->topic;
        $resourceId = $event->resource_id;

        try {
            // Extract customer ID from payload, assuming it's in _links['customer']['href']
            $customerHref = $payload['_links']['customer']['href'] ?? '';
            $customerId = basename($customerHref);
            $customer = DwollaCustomer::where('dwolla_customer_id', $customerId)->first();

            if (! $customer) {
                Log::warning('Dwolla funding source webhook: customer not found', ['customer_id' => $customerId]);
                $event->status = 'failed';
                $event->save();

                return;
            }

            // Find or create the DwollaFundingSource model
            $fundingSource = DwollaFundingSource::firstOrNew(['dwolla_funding_source_id' => $resourceId]);
            if (! $fundingSource->exists) {
                $fundingSource->is_default = false;
            }
            $fundingSource->dwolla_customer_id = $customer->id; // Link to local customer model

            switch ($topic) {

                case 'customer_funding_source_added':
                    $fundingSource->status = 'added';
                    break;

                case 'customer_microdeposits_added':
                    $fundingSource->status = 'microdeposits_initiated';     // Deposits sent by Dwolla
                    $fundingSource->microdeposits_status = 'initiated';
                    $fundingSource->microdeposits_initiated_at = now();
                    break;

                case 'customer_microdeposits_completed':
                    $fundingSource->status = 'microdeposits_completed';     // Deposits successfully posted to bank
                    $fundingSource->microdeposits_status = 'completed';
                    $fundingSource->microdeposits_completed_at = now();
                    break;

                case 'customer_funding_source_verified':
                case 'funding_source_verified':
                    $fundingSource->status = 'verified';
                    $fundingSource->microdeposits_status = 'verified';
                    $fundingSource->microdeposits_completed_at = now();
                    $fundingSource->verified_at = now();
                    break;

                case 'customer_microdeposits_failed':
                    $fundingSource->status = 'microdeposits_failed';
                    $fundingSource->microdeposits_status = 'failed';
                    break;

                case 'customer_microdeposits_maxattempts':
                    $fundingSource->status = 'verification_max_attempts';
                    $fundingSource->microdeposits_status = 'max_attempts_reached';
                    break;

                case 'customer_funding_source_removed':
                    $fundingSource->status = 'removed';
                    break;

                case 'customer_funding_source_updated':
                    $fundingSource->status = 'updated';
                    break;

                default:
                    Log::warning('Dwolla funding source webhook: unhandled topic', [
                        'topic' => $topic,
                        'resource_id' => $resourceId,
                    ]);
                    $event->status = 'ignored';
                    $event->save();

                    return;
            }

            $fundingSource->save();

            if (in_array($topic, ['customer_funding_source_verified', 'funding_source_verified'], true)
                && ! $customer->verifiedDefaultFundingSource()->exists()
            ) {
                $customer->ensureVerifiedDefaultFundingSource();
            }

            $event->status = 'processed';
        } catch (\Exception $e) {
            Log::error('Dwolla funding source webhook processing failed', ['error' => $e->getMessage(), 'event_id' => $this->eventId]);
            $event->status = 'failed';
            $this->fail($e);
        } finally {
            $event->save();
        }
    }
}
