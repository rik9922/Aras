<?php

namespace App\Jobs;

use App\Models\DwollaCustomer;
use App\Models\DwollaWebhookEvent; // Assuming this model exists for Dwolla customers
// Assuming this model exists for funding sources
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDwollaCustomerWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $eventId;

    public function __construct($eventId)
    {
        $this->eventId = $eventId;
    }

    public function handle()
    {
        Log::info('Hello from ProcessDwollaCustomerWebhook');
        $event = DwollaWebhookEvent::find($this->eventId);
        if (! $event) {
            Log::error('Dwolla customer webhook: event not found', ['event_id' => $this->eventId]);

            return;
        }

        $payload = $event->payload;
        $topic = $event->topic;
        $resourceId = $event->resource_id;

        try {
            // Find or create the DwollaCustomer model based on resourceId (Dwolla customer ID)
            $customer = DwollaCustomer::firstOrNew(['dwolla_customer_id' => $resourceId]);

            Log::info('ProcessDwollaCustomerWebhook', [$topic]);

            switch ($topic) {
                case 'customer_created':
                    // Update customer details from payload
                    $customer->status = $payload['status'] ?? 'created'; // Example: set status
                    // Add other fields like name, email, etc., if available in payload
                    // e.g., $customer->name = $payload['name'];
                    break;

                case 'customer_verified':
                    $customer->status = 'verified';
                    // Handle verification logic, e.g., notify user
                    break;

                case 'customer_suspended':
                    $customer->status = 'suspended';
                    break;

                case 'customer_funding_source_verified':
                    $customer->status = 'suspended';
                    break;

                    // Add more customer-related topics as needed, e.g., customer_reverification_needed, etc.

                default:
                    Log::warning('Dwolla customer webhook: unhandled topic', ['topic' => $topic]);
                    $event->status = 'ignored';
                    $event->save();

                    return;
            }

            $customer->save();
            $event->status = 'processed';
        } catch (\Exception $e) {
            Log::error('Dwolla customer webhook processing failed', ['error' => $e->getMessage(), 'event_id' => $this->eventId]);
            $event->status = 'failed';
            $this->fail($e);
        } finally {
            $event->save();
        }
    }
}
