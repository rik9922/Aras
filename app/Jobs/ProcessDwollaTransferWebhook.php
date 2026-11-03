<?php

namespace App\Jobs;

use App\Mail\AmbassadorInvoiceMail;
use App\Models\AmbassadorPayout;
use App\Models\DwollaFundingSource;
use App\Models\DwollaWebhookEvent;
use App\Models\PayrollBatch;
use App\Models\PayrollBatchItem;
use App\Models\Reimbursement;
use App\Models\UserTransaction;
use App\Services\Dwolla\DwollaClient;
use App\Services\AmbassadorNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessDwollaTransferWebhook implements ShouldQueue
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
            Log::error('Dwolla transfer webhook: event not found', ['event_id' => $this->eventId]);

            return;
        }

        $payload = $event->payload;
        $topic = $event->topic;
        $resourceId = $event->resource_id;

        try {
            $client = new DwollaClient;
            $response = $client->get("/transfers/{$resourceId}");

            if (! $response->successful()) {
                throw new \Exception('Failed to fetch transfer details: '.$response->body());
            }

            Log::info('Transfer API response', [$response->body()]);

            $transfer = json_decode($response->body(), true);
            $payoutReference = $transfer['metadata']['payout_reference'] ?? null;

            // If no metadata here, check for linked funding/funded transfer
            if (! $payoutReference) {
                $linkedTransferHref = $transfer['_links']['funding-transfer']['href'] ?? $transfer['_links']['funded-transfer']['href'] ?? null;
                if ($linkedTransferHref) {
                    // Extract path from href
                    $linkedPath = parse_url($linkedTransferHref, PHP_URL_PATH);
                    $responseLinked = $client->get($linkedPath);

                    if ($responseLinked->successful()) {
                        Log::info('Linked Transfer API response', [$responseLinked->body()]);
                        $linkedTransfer = json_decode($responseLinked->body(), true);
                        $payoutReference = $linkedTransfer['metadata']['payout_reference'] ?? null;
                    }
                }
            }

            if (! $payoutReference) {
                Log::warning('Dwolla transfer webhook: payout reference not found in metadata', [
                    'resource_id' => $resourceId,
                    'topic' => $topic,
                    'transfer_metadata' => $transfer['metadata'] ?? 'none',
                ]);
                $event->status = 'failed';
                $event->save();

                return;
            }

            Log::info('Payout Reference found', [$payoutReference]);

            // Extract failure reason from full transfer if available (more reliable than webhook payload)
            $failureReason = $transfer['failure']['description'] ?? $payload['failureReason'] ?? null;

            // Route by reference prefix: PAYOUT-* = event payouts, REIMB-* = reimbursements
            if (str_starts_with($payoutReference, 'REIMB-')) {
                $this->handleReimbursementTransfer($payoutReference, $topic, $resourceId, $failureReason, $event, $transfer);
                return;
            }

            $this->handleEventPayoutTransfer($payoutReference, $topic, $resourceId, $failureReason, $event, $payload, $transfer);
            $event->status = 'processed';
        } catch (\Exception $e) {
            Log::error('Dwolla transfer webhook processing failed', [
                'error' => $e->getMessage(),
                'event_id' => $this->eventId,
                'resource_id' => $resourceId,
                'topic' => $topic,
            ]);
            $event->status = 'failed';
            $this->fail($e);
        } finally {
            $event->save();
        }
    }

    private function handleEventPayoutTransfer(string $payoutReference, string $topic, string $resourceId, ?string $failureReason, DwollaWebhookEvent $event, array $payload, array $transfer): void
    {
        $payout = AmbassadorPayout::where('reference', $payoutReference)->first();
        if (! $payout) {
            Log::warning('Dwolla transfer webhook: payout not found', [
                'payout_reference' => $payoutReference,
                'topic' => $topic,
            ]);
            $event->status = 'failed';

            return;
        }

        if (is_null($payout->dwolla_transfer_id)) {
            $payout->dwolla_transfer_id = $resourceId;
            $payout->save();
        }

        $amount = $transfer['amount']['value'] ?? $payload['amount']['value'] ?? $payout->amount;
        $wasCompleted = $payout->status === 'completed';
        $isCompletedTopic = in_array($topic, [
            'customer_transfer_completed',
            'customer_bank_transfer_completed',
            'transfer_completed',
        ], true);

        switch ($topic) {
            case 'customer_transfer_created':
            case 'customer_bank_transfer_created':
            case 'transfer_created':
                $payout->status = 'processing';
                $payout->processing_at = now();
                break;

            case 'customer_transfer_completed':
            case 'customer_bank_transfer_completed':
            case 'transfer_completed':
                $payout->status = 'completed';
                $payout->completed_at = now();
                break;

            case 'customer_transfer_failed':
            case 'customer_bank_transfer_failed':
            case 'customer_bank_transfer_creation_failed':
            case 'transfer_failed':
            case 'transfer_returned':
                $payout->status = 'failed';
                $payout->failure_reason = $failureReason ?? 'Unknown failure';
                $payout->completed_at = now();
                break;

            case 'customer_transfer_cancelled':
            case 'customer_bank_transfer_cancelled':
            case 'transfer_cancelled':
                $payout->status = 'cancelled';
                $payout->failure_reason = $failureReason ?? 'Cancelled';
                break;

            case 'transfer_pending':
            case 'transfer_processed':
                if ($payout->status !== 'completed' && $payout->status !== 'failed') {
                    $payout->status = 'processing';
                }
                break;

            default:
                Log::warning('Dwolla transfer webhook: unhandled topic for payout', ['topic' => $topic]);
                $event->status = 'ignored';

                return;
        }

        $payout->save();
        $batchItem = $this->normalRecapPayrollBatchItem($payout);

        if ($batchItem) {
            $this->syncNormalRecapReportingStatus($payout, $batchItem);
        }

        if ($payout->status === 'completed' && ! $wasCompleted) {
            $this->sendAmbassadorPaymentNotifications(
                $payout->fresh(['user', 'event.brand', 'fundingSource', 'invoice'])
            );
        }

        if (
            $isCompletedTopic
            && $payout->status === 'completed'
            && $batchItem
            && ! $payout->expense()->exists()
        ) {
            CreateInvoiceForPayout::dispatch($payout)->onQueue('payouts');
        }

        // This record is created only by the immediate expedited path; keep
        // normal Batch Payroll transaction behavior untouched.
        if ((float) $payout->expedited_fee > 0) {
            UserTransaction::updateOrCreate(
                [
                    'user_id' => $payout->user_id,
                    'type' => 'payout',
                    'source_id' => $payout->id,
                    'source_type' => AmbassadorPayout::class,
                ],
                [
                    'amount' => $payout->amount,
                    'currency' => $payout->currency ?? 'USD',
                    'status' => $payout->status,
                    'dwolla_transfer_id' => $payout->dwolla_transfer_id,
                    'description' => 'Expedited ambassador payout - '.$payout->reference,
                    'occurred_at' => now(),
                    'meta' => [
                        'event_id' => $payout->event_id,
                        'original_amount' => $payout->original_amount,
                        'expedited_fee' => $payout->expedited_fee,
                        'final_payout_amount' => $payout->amount,
                    ],
                ]
            );
        }
    }

    private function normalRecapPayrollBatchItem(AmbassadorPayout $payout): ?PayrollBatchItem
    {
        if ((float) $payout->expedited_fee > 0) {
            return null;
        }

        return PayrollBatchItem::query()
            ->where('payment_type', 'recap')
            ->where('ambassador_id', $payout->user_id)
            ->whereHas('recap', function ($query) use ($payout) {
                $query->where('event_id', $payout->event_id);
            })
            ->latest('id')
            ->first();
    }

    private function syncNormalRecapReportingStatus(AmbassadorPayout $payout, PayrollBatchItem $batchItem): void
    {
        $itemStatus = match ($payout->status) {
            'completed' => 'completed',
            'failed', 'cancelled' => 'failed',
            default => 'processing',
        };

        DB::transaction(function () use ($payout, $batchItem, $itemStatus) {
            $batchItem->update([
                'status' => $itemStatus,
                'dwolla_transaction_id' => $payout->dwolla_transfer_id,
                'processed_at' => $itemStatus === 'completed'
                    ? ($payout->completed_at ?? now())
                    : $batchItem->processed_at,
            ]);

            UserTransaction::updateOrCreate(
                [
                    'user_id' => $payout->user_id,
                    'type' => 'payout',
                    'source_id' => $payout->id,
                    'source_type' => AmbassadorPayout::class,
                ],
                [
                    'amount' => $payout->amount,
                    'currency' => $payout->currency ?? 'USD',
                    'status' => $payout->status,
                    'dwolla_transfer_id' => $payout->dwolla_transfer_id,
                    'description' => 'Ambassador payout — '.$payout->reference,
                    'occurred_at' => $payout->completed_at ?? now(),
                    'meta' => [
                        'event_id' => $payout->event_id,
                        'batch_id' => $batchItem->batch_id,
                        'payroll_item_id' => $batchItem->id,
                    ],
                ]
            );

            $this->updatePayrollBatchStatus($batchItem->batch_id);
        });
    }

    private function updatePayrollBatchStatus(int $batchId): void
    {
        $counts = PayrollBatchItem::where('batch_id', $batchId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $failed = (int) ($counts['failed'] ?? 0);
        $completed = (int) ($counts['completed'] ?? 0);
        $processing = (int) ($counts['processing'] ?? 0);
        $created = (int) ($counts['created'] ?? 0);

        $status = match (true) {
            $processing > 0 || $created > 0 => 'processing',
            $failed > 0 => 'failed',
            $completed > 0 => 'completed',
            default => null,
        };

        if ($status) {
            PayrollBatch::where('id', $batchId)->update(['status' => $status]);
        }
    }

    private function handleReimbursementTransfer(string $payoutReference, string $topic, string $resourceId, ?string $failureReason, DwollaWebhookEvent $event, array $transfer): void
    {
        // REIMB-{reimbursement_id}-{uniqid}
        $parts = explode('-', $payoutReference);
        $reimbursementId = $parts[1] ?? null;

        if (! $reimbursementId || ! ctype_digit($reimbursementId)) {
            Log::warning('Dwolla reimbursement webhook: invalid REIMB reference', [
                'payout_reference' => $payoutReference,
                'topic' => $topic,
            ]);
            $event->status = 'failed';

            return;
        }

        $reimbursement = Reimbursement::with('fundingSource')->find($reimbursementId);
        if (! $reimbursement) {
            Log::warning('Dwolla reimbursement webhook: reimbursement not found', [
                'reimbursement_id' => $reimbursementId,
                'payout_reference' => $payoutReference,
                'topic' => $topic,
            ]);
            $event->status = 'failed';

            return;
        }

        if (is_null($reimbursement->dwolla_transfer_id)) {
            $reimbursement->dwolla_transfer_id = $resourceId;
        }

        if (is_null($reimbursement->dwolla_funding_source_id)) {
            $fundingSource = $this->resolveDestinationFundingSource($transfer);
            if ($fundingSource) {
                $reimbursement->dwolla_funding_source_id = $fundingSource->id;
            }
        }

        $wasPaid = $reimbursement->status === 'paid';

        switch ($topic) {
            case 'customer_transfer_created':
            case 'customer_bank_transfer_created':
            case 'transfer_created':
                // Already approved in our system; nothing special to change here
                break;

            case 'customer_transfer_completed':
            case 'customer_bank_transfer_completed':
            case 'transfer_completed':
                $reimbursement->status = 'paid';
                if (! $reimbursement->paid_at) {
                    $reimbursement->paid_at = now();
                }
                break;

            case 'customer_transfer_failed':
            case 'customer_bank_transfer_failed':
            case 'customer_bank_transfer_creation_failed':
            case 'transfer_failed':
            case 'transfer_returned':
                Log::warning('Dwolla reimbursement transfer failed', [
                    'reimbursement_id' => $reimbursement->id,
                    'reason' => $failureReason ?? 'Unknown failure',
                    'topic' => $topic,
                ]);
                break;

            case 'customer_transfer_cancelled':
            case 'customer_bank_transfer_cancelled':
            case 'transfer_cancelled':
                Log::warning('Dwolla reimbursement transfer cancelled', [
                    'reimbursement_id' => $reimbursement->id,
                    'reason' => $failureReason ?? 'Cancelled',
                    'topic' => $topic,
                ]);
                break;

            default:
                Log::warning('Dwolla reimbursement webhook: unhandled topic', ['topic' => $topic]);
                $event->status = 'ignored';

                return;
        }

        $reimbursement->save();
        $reimbursement->load('fundingSource');

        if ($reimbursement->status === 'paid' && ! $wasPaid) {
            $this->sendAmbassadorPaymentNotifications(
                $reimbursement->fresh(['user', 'event.brand', 'brand', 'fundingSource'])
            );
        }

        $transaction = UserTransaction::where([
            'user_id' => $reimbursement->user_id,
            'type' => 'reimbursement',
            'source_id' => $reimbursement->id,
            'source_type' => Reimbursement::class,
        ])->first();

        $meta = array_merge($transaction?->meta ?? [], [
            'event_id' => $reimbursement->event_id,
            'brand_id' => $reimbursement->brand_id,
        ]);

        if ($reimbursement->fundingSource) {
            $meta['bank_name'] = $reimbursement->fundingSource->bank_name;
            $meta['account_last4'] = $reimbursement->fundingSource->account_number
                ? substr((string) $reimbursement->fundingSource->account_number, -4)
                : null;
        }

        // Upsert unified transaction record for this reimbursement
        UserTransaction::updateOrCreate(
            [
                'user_id' => $reimbursement->user_id,
                'type' => 'reimbursement',
                'source_id' => $reimbursement->id,
                'source_type' => Reimbursement::class,
            ],
            [
                'amount' => $reimbursement->amount,
                'currency' => 'USD',
                'status' => $reimbursement->status,
                'dwolla_transfer_id' => $reimbursement->dwolla_transfer_id,
                'description' => $reimbursement->description_for_quickbooks,
                'occurred_at' => $reimbursement->paid_at ?? $reimbursement->updated_at ?? now(),
                'meta' => $meta,
            ]
        );
    }

    private function resolveDestinationFundingSource(array $transfer): ?DwollaFundingSource
    {
        $href = $transfer['_links']['destination']['href'] ?? null;
        if (! $href) {
            return null;
        }

        $fundingSourceId = basename((string) parse_url($href, PHP_URL_PATH));

        return $fundingSourceId
            ? DwollaFundingSource::where('dwolla_funding_source_id', $fundingSourceId)->first()
            : null;
    }

    private function sendAmbassadorPaymentNotifications(AmbassadorPayout|Reimbursement|null $payment): void
    {
        if (! $payment?->user) {
            return;
        }

        $isReimbursement = $payment instanceof Reimbursement;

        try {
            $service = app(AmbassadorNotificationService::class);
            $isReimbursement
                ? $service->reimbursementPaymentReceived($payment)
                : $service->eventPayoutReceived($payment);
        } catch (\Throwable $e) {
            Log::error('Ambassador completed payment push failed.', [
                'payment_type' => $isReimbursement ? 'reimbursement' : 'payout',
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'error' => $e->getMessage(),
            ]);
        }

        if ($payment->user->email) {
            try {
                Mail::to($payment->user->email)->send(new AmbassadorInvoiceMail($payment));
            } catch (\Throwable $e) {
                Log::error('Ambassador completed payment email failed.', [
                    'payment_type' => $isReimbursement ? 'reimbursement' : 'payout',
                    'payment_id' => $payment->id,
                    'user_id' => $payment->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
