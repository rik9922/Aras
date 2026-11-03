<?php

namespace App\Jobs;

use App\Jobs\CreateInvoiceForPayout;
use App\Models\AmbassadorPayout;
use App\Models\PayrollBatch;
use App\Models\PayrollBatchItem;
use App\Models\UserTransaction;
use App\Services\Dwolla\DwollaTransferService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPayrollBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public int $batchId,
        public int $processedBy
    ) {}

    public function handle(DwollaTransferService $dwolla): void
    {
        $batch = PayrollBatch::find($this->batchId);

        if (! $batch) {
            Log::error('[ProcessPayrollBatch] Batch not found', ['batch_id' => $this->batchId]);
            return;
        }

        Log::info('[ProcessPayrollBatch] Starting', [
            'batch_number' => $batch->batch_number,
            'batch_id'     => $batch->id,
        ]);

        // Mark the batch as processing
        $batch->update([
            'status'       => 'processing',
            'processed_by' => $this->processedBy,
            'processed_at' => now(),
        ]);

        // Fetch only eligible items: status = created
        $items = PayrollBatchItem::where('batch_id', $batch->id)
            ->where('status', 'created')
            ->get();

        if ($items->isEmpty()) {
            Log::info('[ProcessPayrollBatch] No eligible items (created status)', [
                'batch_number' => $batch->batch_number,
            ]);
            $this->updateBatchFinalStatus($batch);
            return;
        }

        foreach ($items as $item) {
            $this->processItem($item, $batch, $dwolla);
        }

        $this->updateBatchFinalStatus($batch);

        Log::info('[ProcessPayrollBatch] Finished', [
            'batch_number' => $batch->batch_number,
        ]);
    }

    private function processItem(PayrollBatchItem $item, PayrollBatch $batch, DwollaTransferService $dwolla): void
    {
        // Safety guard — skip if already processing or completed
        $item->refresh();
        if (in_array($item->status, ['processing', 'completed'])) {
            Log::info('[ProcessPayrollBatch] Skipping item (already processing/completed)', [
                'batch_number'   => $batch->batch_number,
                'payroll_item_id' => $item->id,
                'status'         => $item->status,
            ]);
            return;
        }

        // Resolve the linked AmbassadorPayout via event_id + ambassador_id
        // The recap carries event_id; the batch item stores ambassador_id directly.
        $recap = $item->recap;

        if (! $recap) {
            Log::error('[ProcessPayrollBatch] Recap not found for item', [
                'batch_number'   => $batch->batch_number,
                'payroll_item_id' => $item->id,
                'reference_id'   => $item->reference_id,
            ]);
            $item->update(['status' => 'failed']);
            return;
        }

        $payout = AmbassadorPayout::where('event_id', $recap->event_id)
            ->where('user_id', $item->ambassador_id)
            ->first();

        if (! $payout) {
            Log::error('[ProcessPayrollBatch] AmbassadorPayout not found for item', [
                'batch_number'   => $batch->batch_number,
                'payroll_item_id' => $item->id,
                'ambassador_id'  => $item->ambassador_id,
                'event_id'       => $recap->event_id,
            ]);
            $item->update(['status' => 'failed']);
            return;
        }

        // Step 1: Mark both as processing (wrapped in transaction to stay consistent)
        DB::transaction(function () use ($item, $payout) {
            $item->update(['status' => 'processing']);

            // Only advance if still in queued state
            if ($payout->status === 'queued') {
                $payout->update([
                    'status'       => 'processing',
                    'processing_at' => now(),
                ]);
            }
        });

        Log::info('[ProcessPayrollBatch] Processing item', [
            'batch_number'    => $batch->batch_number,
            'payroll_item_id' => $item->id,
            'ambassador_id'   => $item->ambassador_id,
            'amount'          => $item->amount,
        ]);

        try {
            // Step 2: Execute the Dwolla transfer using the existing service
            $transferId = $dwolla->create($payout);

            // Step 3: Transfer created — keep payment open until Dwolla confirms completion.
            DB::transaction(function () use ($item, $payout, $transferId) {
                $item->update([
                    'status'               => 'processing',
                    'dwolla_transaction_id' => $transferId,
                ]);

                $payout->update([
                    'dwolla_transfer_id' => $transferId,
                    'status'             => 'processing',
                ]);
            });

            // Step 4: Dispatch QuickBooks expense creation (reuse existing job).
            // Must use 'payouts' queue — the worker that runs ProcessPayrollBatch listens
            // to 'payouts', not 'invoices'. Dispatching to 'invoices' left jobs unprocessed.
            CreateInvoiceForPayout::dispatch($payout)->onQueue('payouts');

            // Step 5: Upsert the user_transactions record (reuse existing flow)
            UserTransaction::updateOrCreate(
                [
                    'user_id'     => $payout->user_id,
                    'type'        => 'payout',
                    'source_id'   => $payout->id,
                    'source_type' => AmbassadorPayout::class,
                ],
                [
                    'amount'           => $payout->amount,
                    'currency'         => $payout->currency ?? 'USD',
                    'status'           => 'processing',
                    'dwolla_transfer_id' => $transferId,
                    'description'      => 'Ambassador payout — ' . $payout->reference,
                    'occurred_at'      => now(),
                    'meta'             => [
                        'event_id'        => $payout->event_id,
                        'batch_id'        => $item->batch_id,
                        'payroll_item_id' => $item->id,
                    ],
                ]
            );

            Log::info('[ProcessPayrollBatch] Item succeeded', [
                'batch_number'       => $batch->batch_number,
                'payroll_item_id'    => $item->id,
                'ambassador_id'      => $item->ambassador_id,
                'amount'             => $item->amount,
                'dwolla_transfer_id' => $transferId,
            ]);

        } catch (\Throwable $e) {
            // Step 5: Failure — update item + payout, continue with remaining
            DB::transaction(function () use ($item, $payout, $e) {
                $item->update(['status' => 'failed']);

                $payout->update([
                    'status'         => 'failed',
                    'failure_reason' => $e->getMessage(),
                ]);
            });

            Log::error('[ProcessPayrollBatch] Item failed', [
                'batch_number'   => $batch->batch_number,
                'payroll_item_id' => $item->id,
                'ambassador_id'  => $item->ambassador_id,
                'amount'         => $item->amount,
                'error'          => $e->getMessage(),
            ]);
            // Do NOT rethrow — continue processing remaining items
        }
    }

    /**
     * Derive and persist the final batch status from its item outcomes.
     *
     * Rules:
     *   all completed            → completed
     *   any failed, rest done    → failed
     *   any still processing     → processing (shouldn't happen in sync flow)
     *   all still created        → created
     */
    private function updateBatchFinalStatus(PayrollBatch $batch): void
    {
        $counts = PayrollBatchItem::where('batch_id', $batch->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $failed     = (int) ($counts['failed']     ?? 0);
        $completed  = (int) ($counts['completed']  ?? 0);
        $processing = (int) ($counts['processing'] ?? 0);
        $created    = (int) ($counts['created']    ?? 0);
        $removed    = (int) ($counts['removed']    ?? 0);

        $activeTotal = $failed + $completed + $processing + $created;

        if ($processing > 0 || $created > 0) {
            $finalStatus = 'processing';
        } elseif ($failed > 0) {
            $finalStatus = 'failed';
        } elseif ($completed > 0) {
            $finalStatus = 'completed';
        } else {
            $finalStatus = $batch->status; // unchanged (edge case: all removed)
        }

        $batch->update(['status' => $finalStatus]);

        Log::info('[ProcessPayrollBatch] Batch final status set', [
            'batch_number' => $batch->batch_number,
            'status'       => $finalStatus,
            'counts'       => $counts->toArray(),
        ]);
    }
}
