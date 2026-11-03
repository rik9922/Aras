<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessQuickBooksWebhookEvent;
use App\Models\QuickBooksWebhookEvent;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QuickBooksWebhookController extends Controller
{
    public function handle(Request $request)
    {
        if (! $this->isValidSignature($request)) {
            Log::warning('QuickBooks webhook: invalid signature');

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $payload = json_decode($request->getContent(), true);
        if (! is_array($payload)) {
            Log::warning('QuickBooks webhook: invalid JSON payload');

            return response()->json(['status' => 'ignored'], 200);
        }

        $stored = 0;
        $duplicates = 0;

        foreach ($payload['eventNotifications'] ?? [] as $notification) {
            $realmId = (string) ($notification['realmId'] ?? $request->header('intuitaccountid', ''));
            $entities = $notification['dataChangeEvent']['entities'] ?? [];

            foreach ($entities as $entity) {
                $entityType = (string) ($entity['name'] ?? '');
                $entityId = (string) ($entity['id'] ?? '');
                $operation = strtolower((string) ($entity['operation'] ?? ''));

                if ($entityType === '' || $entityId === '' || $operation === '') {
                    continue;
                }

                $eventHash = $this->eventHash($realmId, $entityType, $entityId, $operation, $entity['lastUpdated'] ?? null);

                $eventData = [
                    'realm_id' => $realmId !== '' ? $realmId : null,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'operation' => $operation,
                    'quickbooks_updated_at' => $this->parseQuickBooksTimestamp($entity['lastUpdated'] ?? null),
                    'event_hash' => $eventHash,
                    'payload' => [
                        'notification' => $notification,
                        'entity' => $entity,
                    ],
                    'headers' => $this->safeHeaders($request),
                    'status' => 'received',
                ];

                try {
                    $event = QuickBooksWebhookEvent::create($eventData);
                } catch (QueryException $e) {
                    if (! $this->isDuplicateEventException($e)) {
                        throw $e;
                    }

                    $event = QuickBooksWebhookEvent::where('event_hash', $eventHash)->first();
                    if ($event && $event->status === 'failed') {
                        $event->update([
                            'status' => 'received',
                            'failure_reason' => null,
                            'payload' => $eventData['payload'],
                            'headers' => $eventData['headers'],
                        ]);
                    } else {
                        $duplicates++;
                        continue;
                    }

                    $duplicates++;
                }

                if ($this->isSupportedEntity($entityType)) {
                    ProcessQuickBooksWebhookEvent::dispatch($event->id);
                } else {
                    $event->update([
                        'status' => 'ignored',
                        'failure_reason' => 'Unsupported QuickBooks entity type.',
                    ]);
                }

                $stored++;
            }
        }

        return response()->json([
            'status' => 'ok',
            'stored' => $stored,
            'duplicates' => $duplicates,
        ], 200);
    }

    private function isValidSignature(Request $request): bool
    {
        $signature = (string) $request->header('intuit-signature', '');
        $verifierToken = (string) config('quickbooks.webhook_verifier_token');

        if ($signature === '' || $verifierToken === '') {
            return false;
        }

        $computed = base64_encode(hash_hmac('sha256', $request->getContent(), $verifierToken, true));

        return hash_equals($computed, $signature);
    }

    private function eventHash(string $realmId, string $entityType, string $entityId, string $operation, ?string $lastUpdated): string
    {
        return hash('sha256', implode('|', [$realmId, $entityType, $entityId, $operation, (string) $lastUpdated]));
    }

    private function parseQuickBooksTimestamp(?string $timestamp): ?Carbon
    {
        if (! $timestamp) {
            return null;
        }

        try {
            return Carbon::parse($timestamp);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function isSupportedEntity(string $entityType): bool
    {
        return in_array(strtolower($entityType), [
            'account',
            'customer',
            'invoice',
            'item',
            'payment',
            'paymentmethod',
            'purchase',
            'term',
            'vendor',
        ], true);
    }

    private function isDuplicateEventException(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $driverCode = (string) ($e->errorInfo[1] ?? '');

        return $sqlState === '23000' || in_array($driverCode, ['1062', '19', '23505'], true);
    }

    private function safeHeaders(Request $request): array
    {
        return collect($request->headers->all())
            ->only(['intuit-signature', 'intuitaccountid', 'content-type', 'user-agent'])
            ->toArray();
    }
}
