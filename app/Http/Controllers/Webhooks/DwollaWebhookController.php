<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDwollaCustomerWebhook;
use App\Jobs\ProcessDwollaFundingSourceWebhook;
use App\Jobs\ProcessDwollaTransferWebhook;
use App\Models\DwollaWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DwollaWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verify signature
        if (! $this->isValidSignature($request)) {
            Log::warning('Dwolla webhook: invalid signature');

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // Decode payload
        $payload = json_decode($request->getContent(), true);
        $eventId = $payload['id'] ?? null;
        $topic = $payload['topic'] ?? null;
        $resource_href = $payload['_links']['resource']['href'] ?? '';
        $resourceId = $payload['resourceId'] ?? basename($resource_href);
        if (! $topic || ! $resourceId || ! $resource_href) {
            return response()->json(['status' => 'ignored'], 200);
        }
        // Determine resource_type from the resource href
        $resource_type = $this->determineResourceType($resource_href);
        if ($resource_type === 'unknown') {
            Log::warning('Dwolla webhook: unknown resource type', ['topic' => $topic]);

            return response()->json(['status' => 'ignored'], 200);
        }
        // Business-level idempotency
        $alreadyProcessed = DwollaWebhookEvent::where('topic', $topic)
            ->where('resource_id', $resourceId)
            ->where('resource_type', $resource_type)
            ->where('status', 'processed')
            ->exists();
        if ($alreadyProcessed) {
            return response()->json(['status' => 'duplicate'], 200);
        }
        // Store webhook (RAW)
        $event = DwollaWebhookEvent::create([
            'event_id' => $eventId,
            'topic' => $topic,
            'resource_id' => $resourceId,
            'resource_type' => $resource_type,
            'payload' => $payload,
            'status' => 'received',
        ]);
        // Dispatch based on resource_type
        if ($resource_type === 'funding_source') {
            ProcessDwollaFundingSourceWebhook::dispatch($event->id);
        } elseif ($resource_type === 'customer') {
            ProcessDwollaCustomerWebhook::dispatch($event->id);
        } elseif ($resource_type === 'transfer') {
            ProcessDwollaTransferWebhook::dispatch($event->id);
        } else {
            // For other types, log or handle if needed
            Log::info('Dwolla webhook: unhandled resource type', ['type' => $resource_type, 'topic' => $topic]);
        }

        // Respond immediately
        return response()->json(['status' => 'ok'], 200);
    }

    protected function isValidSignature(Request $request): bool
    {
        $dwollaSignature = $request->header('X-Request-Signature-SHA-256');

        if (! $dwollaSignature) {
            return false;
        }

        $secret = config('services.dwolla.webhook_secret');

        // IMPORTANT: raw body exactly as received
        $rawPayload = $request->getContent();

        // Compute hex-encoded HMAC
        $computedSignature = hash_hmac('sha256', $rawPayload, $secret);

        return hash_equals($computedSignature, $dwollaSignature);
    }

    protected function determineResourceType(string $href): string
    {
        if (empty($href)) {
            return 'unknown';
        }
        $path = parse_url($href, PHP_URL_PATH);
        if (! $path) {
            return 'unknown';
        }
        $parts = array_filter(explode('/', trim($path, '/')));
        if (count($parts) < 2) {
            return 'unknown';
        }
        $resource_type_plural = reset($parts);
        $resource_type = str_replace('-', '_', rtrim($resource_type_plural, 's'));

        return $resource_type;
    }
}
