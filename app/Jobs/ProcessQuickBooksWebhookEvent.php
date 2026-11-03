<?php

namespace App\Jobs;

use App\Models\QuickBooksWebhookEvent;
use App\Services\QuickBooksService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessQuickBooksWebhookEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $eventId;

    public function __construct(int $eventId)
    {
        $this->eventId = $eventId;
    }

    public function handle(QuickBooksService $quickBooksService): void
    {
        $event = QuickBooksWebhookEvent::find($this->eventId);
        if (! $event) {
            Log::error('QuickBooks webhook: event not found', ['event_id' => $this->eventId]);

            return;
        }

        if ($event->status === 'processed') {
            return;
        }

        try {
            $result = match (strtolower((string) $event->entity_type)) {
                'account' => $this->syncAccount($event, $quickBooksService),
                'customer' => $this->syncCustomer($event, $quickBooksService),
                'invoice' => $this->syncInvoice($event, $quickBooksService),
                'item' => $this->syncItem($event, $quickBooksService),
                'payment' => $this->syncPayment($event, $quickBooksService),
                'paymentmethod' => $this->syncPaymentMethod($event, $quickBooksService),
                'purchase' => $this->syncPurchase($event, $quickBooksService),
                'term' => $this->syncTerm($event, $quickBooksService),
                'vendor' => $this->syncVendor($event, $quickBooksService),
                default => ['success' => true, 'status' => 'ignored', 'message' => 'Unsupported QuickBooks entity type.'],
            };

            $event->status = $result['status'] ?? ($result['success'] ? 'processed' : 'failed');
            $event->failure_reason = $result['success'] ? null : ($result['message'] ?? 'QuickBooks webhook processing failed.');
            $event->processed_at = now();
        } catch (\Throwable $e) {
            Log::error('QuickBooks webhook processing failed', [
                'event_id' => $event->id,
                'entity_type' => $event->entity_type,
                'entity_id' => $event->entity_id,
                'operation' => $event->operation,
                'error' => $e->getMessage(),
            ]);

            $event->status = 'failed';
            $event->failure_reason = $e->getMessage();
            $event->processed_at = now();

            $this->fail($e);
        } finally {
            $event->save();
        }
    }

    private function syncPurchase(QuickBooksWebhookEvent $event, QuickBooksService $quickBooksService): array
    {
        if ($event->operation === 'delete') {
            return $quickBooksService->deleteLocalExpenseByQuickBooksId((string) $event->entity_id);
        }

        return $quickBooksService->syncExpenseByQuickBooksId((string) $event->entity_id);
    }

    private function syncInvoice(QuickBooksWebhookEvent $event, QuickBooksService $quickBooksService): array
    {
        if ($event->operation === 'delete') {
            return $quickBooksService->markLocalInvoiceDeletedByQuickBooksId((string) $event->entity_id);
        }

        return $quickBooksService->syncInvoiceByQuickBooksId((string) $event->entity_id);
    }

    private function syncCustomer(QuickBooksWebhookEvent $event, QuickBooksService $quickBooksService): array
    {
        if ($event->operation === 'delete') {
            return $quickBooksService->unlinkLocalCustomerByQuickBooksId((string) $event->entity_id);
        }

        return $quickBooksService->syncCustomerByQuickBooksId((string) $event->entity_id);
    }

    private function syncVendor(QuickBooksWebhookEvent $event, QuickBooksService $quickBooksService): array
    {
        if ($event->operation === 'delete') {
            return $quickBooksService->unlinkLocalVendorByQuickBooksId((string) $event->entity_id);
        }

        return $quickBooksService->syncVendorByQuickBooksId((string) $event->entity_id);
    }

    private function syncAccount(QuickBooksWebhookEvent $event, QuickBooksService $quickBooksService): array
    {
        if ($event->operation === 'delete') {
            return $quickBooksService->deactivateLocalAccountByQuickBooksId((string) $event->entity_id);
        }

        return $quickBooksService->syncAccountByQuickBooksId((string) $event->entity_id);
    }

    private function syncItem(QuickBooksWebhookEvent $event, QuickBooksService $quickBooksService): array
    {
        if ($event->operation === 'delete') {
            return $quickBooksService->deactivateLocalServiceByQuickBooksItemId((string) $event->entity_id);
        }

        return $quickBooksService->syncServiceItemByQuickBooksId((string) $event->entity_id);
    }

    private function syncTerm(QuickBooksWebhookEvent $event, QuickBooksService $quickBooksService): array
    {
        if ($event->operation === 'delete') {
            return $quickBooksService->deactivateLocalTermByQuickBooksId((string) $event->entity_id);
        }

        return $quickBooksService->syncTermByQuickBooksId((string) $event->entity_id);
    }

    private function syncPaymentMethod(QuickBooksWebhookEvent $event, QuickBooksService $quickBooksService): array
    {
        if ($event->operation === 'delete') {
            return $quickBooksService->deactivateLocalPaymentMethodByQuickBooksId((string) $event->entity_id);
        }

        return $quickBooksService->syncPaymentMethodByQuickBooksId((string) $event->entity_id);
    }

    private function syncPayment(QuickBooksWebhookEvent $event, QuickBooksService $quickBooksService): array
    {
        if ($event->operation === 'delete') {
            return ['success' => true, 'status' => 'ignored', 'message' => 'QuickBooks payment delete has no linked invoice details in the webhook payload.'];
        }

        return $quickBooksService->syncInvoicesLinkedToPaymentByQuickBooksId((string) $event->entity_id);
    }
}
