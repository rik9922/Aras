<?php

namespace App\Services\Dwolla;

use App\Models\DwollaWebhookSubscription;
use Illuminate\Support\Carbon;

class WebhookSubscriptionService
{
    protected DwollaClient $client;

    public function __construct(?DwollaClient $client = null)
    {
        $this->client = $client ?? new DwollaClient;
    }

    /**
     * Create a webhook subscription on Dwolla.
     * Requires url and secret (e.g. from config('services.dwolla.webhook_secret')).
     */
    public function create(string $url, string $secret): DwollaWebhookSubscription
    {
        $response = $this->client->post('/webhook-subscriptions', [
            'url' => $url,
            'secret' => $secret,
        ]);

        if (! $response->successful()) {
            $body = $response->json();
            $message = $body['message'] ?? 'Dwolla webhook subscription creation failed';
            if (! empty($body['_embedded']['errors'])) {
                $messages = array_column($body['_embedded']['errors'], 'message');
                $message .= ': '.implode(' ', $messages);
            }
            throw new \RuntimeException($message);
        }

        $location = $response->header('Location');
        if (! $location) {
            throw new \RuntimeException('Dwolla did not return webhook subscription location.');
        }

        $dwollaSubscriptionId = basename(parse_url($location, PHP_URL_PATH));

        $subscription = DwollaWebhookSubscription::create([
            'dwolla_subscription_id' => $dwollaSubscriptionId,
            'url' => $url,
            'paused' => false,
            'dwolla_created_at' => now(),
        ]);

        return $subscription;
    }

    /**
     * List all webhook subscriptions from Dwolla and sync to local records.
     */
    public function listFromDwolla(): array
    {
        $response = $this->client->get('/webhook-subscriptions');

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to list Dwolla webhook subscriptions.');
        }

        $data = $response->json();
        $items = $data['_embedded']['webhook-subscriptions'] ?? [];
        $result = [];

        foreach ($items as $item) {
            $subscription = $this->upsertSubscriptionFromPayload($item);
            $result[] = $subscription;
        }

        return $result;
    }

    /**
     * Get a single subscription from Dwolla by id and sync to local.
     */
    public function get(string $dwollaSubscriptionId): ?DwollaWebhookSubscription
    {
        $response = $this->client->get("/webhook-subscriptions/{$dwollaSubscriptionId}");

        if (! $response->successful()) {
            return null;
        }

        return $this->upsertSubscriptionFromPayload($response->json());
    }

    /**
     * Pause or resume a webhook subscription on Dwolla.
     */
    public function updatePaused(string $dwollaSubscriptionId, bool $paused): DwollaWebhookSubscription
    {
        $response = $this->client->post("/webhook-subscriptions/{$dwollaSubscriptionId}", [
            'paused' => $paused,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to update Dwolla webhook subscription.');
        }

        $subscription = DwollaWebhookSubscription::firstWhere('dwolla_subscription_id', $dwollaSubscriptionId);
        if ($subscription) {
            $subscription->update(['paused' => $paused]);
        } else {
            $subscription = $this->upsertSubscriptionFromPayload($response->json());
        }

        return $subscription;
    }

    /**
     * Delete a webhook subscription on Dwolla and optionally remove local record.
     */
    public function delete(string $dwollaSubscriptionId, bool $deleteLocal = true): void
    {
        $response = $this->client->delete("/webhook-subscriptions/{$dwollaSubscriptionId}");

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to delete Dwolla webhook subscription.');
        }

        if ($deleteLocal) {
            DwollaWebhookSubscription::where('dwolla_subscription_id', $dwollaSubscriptionId)->delete();
        }
    }

    protected function upsertSubscriptionFromPayload(array $item): DwollaWebhookSubscription
    {
        $dwollaId = $item['id'] ?? null;
        if (! $dwollaId) {
            throw new \InvalidArgumentException('Invalid Dwolla webhook subscription payload: missing id');
        }

        $created = isset($item['created'])
            ? Carbon::parse($item['created'])
            : null;

        return DwollaWebhookSubscription::updateOrCreate(
            ['dwolla_subscription_id' => $dwollaId],
            [
                'url' => $item['url'] ?? '',
                'paused' => (bool) ($item['paused'] ?? false),
                'dwolla_created_at' => $created,
            ]
        );
    }
}
