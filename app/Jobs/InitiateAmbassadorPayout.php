<?php

namespace App\Jobs;

use App\Models\AmbassadorPayout;
use App\Models\UserTransaction;
use App\Services\Dwolla\DwollaTransferService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InitiateAmbassadorPayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(public AmbassadorPayout $payout) {}

    public function handle(DwollaTransferService $dwolla)
    {
        $expeditedLog = Log::channel('expedited_pay');
        $payout = $this->payout->fresh();
        $isExpedited = (float) $payout->expedited_fee > 0;

        if ($isExpedited) {
            $expeditedLog->info('InitiateAmbassadorPayout started for expedited payout.', [
                'payout_id' => $payout->id,
                'event_id' => $payout->event_id,
                'ambassador_id' => $payout->user_id,
                'status' => $payout->status,
                'gross_amount' => $payout->original_amount,
                'expedited_fee' => $payout->expedited_fee,
                'net_amount' => $payout->amount,
            ]);
        }

        // idempotency guard
        if ($payout->status !== 'queued') {
            if ($isExpedited) {
                $expeditedLog->warning('InitiateAmbassadorPayout skipped expedited payout because status is not queued.', [
                    'payout_id' => $payout->id,
                    'event_id' => $payout->event_id,
                    'ambassador_id' => $payout->user_id,
                    'status' => $payout->status,
                ]);
            }

            return;
        }

        $payout->update([
            'status' => 'processing',
            'processing_at' => now(),
            'retry_count' => $payout->retry_count + 1,
            'last_retry_at' => now(),
        ]);

        try {
            $transferId = $dwolla->create($payout);
        } catch (\Throwable $e) {
            if ($isExpedited) {
                $expeditedLog->error('Dwolla transfer creation failed for expedited payout.', [
                    'payout_id' => $payout->id,
                    'event_id' => $payout->event_id,
                    'ambassador_id' => $payout->user_id,
                    'status' => $payout->status,
                    'gross_amount' => $payout->original_amount,
                    'expedited_fee' => $payout->expedited_fee,
                    'net_amount' => $payout->amount,
                    'exception_class' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            throw $e;
        }

        $payout->update([
            'dwolla_transfer_id' => $transferId,
            'status' => 'processing',
        ]);

        // Immediate expedited payouts do not pass through ProcessPayrollBatch,
        // so create the corresponding reporting transaction here. The webhook
        // will advance its status when Dwolla completes or fails the transfer.
        if ($isExpedited) {
            $expeditedLog->info('Dwolla transfer created for expedited payout.', [
                'payout_id' => $payout->id,
                'event_id' => $payout->event_id,
                'ambassador_id' => $payout->user_id,
                'status' => $payout->status,
                'dwolla_transfer_id' => $transferId,
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
                    'status' => 'processing',
                    'dwolla_transfer_id' => $transferId,
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

        CreateInvoiceForPayout::dispatch($payout)->onQueue('invoices');
    }

    public function failed(\Throwable $e)
    {
        $payout = $this->payout->fresh();

        if ($payout && (float) $payout->expedited_fee > 0) {
            Log::channel('expedited_pay')->error('InitiateAmbassadorPayout failed for expedited payout.', [
                'payout_id' => $payout->id,
                'event_id' => $payout->event_id,
                'ambassador_id' => $payout->user_id,
                'status' => $payout->status,
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        $this->payout->update([
            'last_retry_at' => now(),
            'status' => 'job_failed',
            'failure_reason' => $e->getMessage(),
        ]);
        // Admin notification will come later (Step 5)
    }
}
