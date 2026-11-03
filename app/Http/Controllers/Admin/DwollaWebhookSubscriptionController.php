<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DwollaWebhookSubscription;
use App\Services\Dwolla\WebhookSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DwollaWebhookSubscriptionController extends Controller
{
    public function __construct(
        protected WebhookSubscriptionService $subscriptionService
    ) {}

    /**
     * List webhook subscriptions (from local DB). Optionally sync from Dwolla.
     */
    public function index(Request $request)
    {
        if ($request->has('sync') && $request->get('sync') === '1') {
            try {
                $this->subscriptionService->listFromDwolla();

                return redirect()->route('admin.webhooks.index')->with('success', 'Subscriptions synced from Dwolla.');
            } catch (\Throwable $e) {
                return redirect()->route('admin.webhooks.index')->with('error', 'Sync failed: '.$e->getMessage());
            }
        }

        $subscriptions = DwollaWebhookSubscription::orderByDesc('created_at')->get();

        return view('admin.webhooks.index', compact('subscriptions'));
    }

    /**
     * Show form to create a new webhook subscription.
     */
    public function create()
    {
        $defaultUrl = rtrim(config('app.url'), '/').'/api/webhooks/dwolla';

        return view('admin.webhooks.create', compact('defaultUrl'));
    }

    /**
     * Store a new webhook subscription (create on Dwolla and locally).
     */
    public function store(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'secret' => 'nullable|string|max:255',
        ]);

        $url = $request->input('url');
        $secret = $request->input('secret') ?: config('services.dwolla.webhook_secret');

        if (empty($secret)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Webhook secret is required. Set DWOLLA_WEBHOOK_SECRET in .env or enter it in the form.');
        }

        try {
            $this->subscriptionService->create($url, $secret);

            return redirect()->route('admin.webhooks.index')->with('success', 'Webhook subscription created successfully.');
        } catch (\Throwable $e) {
            Log::info('Failed to create webhook subscription: '.$e->getMessage());

            $error = $e->getMessage();
            if (stripos($error, 'valid url') !== false || stripos($error, 'Url must be') !== false) {
                if (preg_match('#localhost|127\.0\.0\.1#i', $url)) {
                    $error .= ' Dwolla does not accept localhost URLs. Use a publicly accessible URL (e.g. ngrok or your staging/production domain).';
                }
            }

            return redirect()->back()->withInput()->with('error', $error);
        }
    }

    /**
     * Toggle paused state (pause or resume).
     */
    public function updatePaused(Request $request, string $id)
    {
        $subscription = DwollaWebhookSubscription::findOrFail($id);

        try {
            $this->subscriptionService->updatePaused($subscription->dwolla_subscription_id, ! $subscription->paused);
            $message = $subscription->paused ? 'Webhook subscription resumed.' : 'Webhook subscription paused.';

            return redirect()->route('admin.webhooks.index')->with('success', $message);
        } catch (\Throwable $e) {
            return redirect()->route('admin.webhooks.index')->with('error', $e->getMessage());
        }
    }

    /**
     * Delete webhook subscription (on Dwolla and locally).
     */
    public function destroy(string $id)
    {
        $subscription = DwollaWebhookSubscription::findOrFail($id);

        try {
            $this->subscriptionService->delete($subscription->dwolla_subscription_id, true);

            return redirect()->route('admin.webhooks.index')->with('success', 'Webhook subscription deleted.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.webhooks.index')->with('error', $e->getMessage());
        }
    }
}
