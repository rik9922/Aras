<?php

namespace App\Services;

use App\Models\AmbassadorPayout;
use App\Models\AssignAmbassador;
use App\Models\PayrollBatch;
use App\Models\PayrollBatchItem;
use App\Models\Recap;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollBatchService
{
    /**
     * Generate a payroll batch for all eligible approved recaps.
     *
     * Eligibility rules (ALL must be true):
     * - Recap is not a draft         (is_draft = false)
     * - Recap is approved            (is_approved = true)
     * - Recap has not been denied    (denied_at IS NULL)
     * - Recap APPROVAL DATE falls within period_start..period_end
     *   ── NOT event date / event start / recap submitted date ──
     * - Event is completed           (event_status = '1')
     * - Ambassador has NOT already been paid for this event
     *   (no AmbassadorPayout with status = 'completed')
     * - Recap NOT already in an active payroll batch item
     *   ('removed' items excluded from duplicate check — recap can be re-batched)
     *
     * During generation, the related AmbassadorPayout is updated:
     *   approved → queued  (only for payouts successfully added to the batch)
     *
     * Lifecycle mapping:
     *   Recap approved         → AmbassadorPayout = approved
     *   Generate Payroll       → AmbassadorPayout = queued  | batch_item = created
     *   Process Payroll (TBD)  → AmbassadorPayout = processing | batch_item = processing
     *   Payment completed      → AmbassadorPayout = completed | batch_item = completed
     *   Payment failed         → AmbassadorPayout = failed     | batch_item = failed
     *
     * @throws \RuntimeException when no eligible recaps are found
     */
    public function generate(string $periodStart, string $periodEnd): PayrollBatch
    {
        // ── DEBUG: trace each filter step to find which condition removes records ──
        $base = Recap::query()->where('is_draft', false)->where('is_approved', true)->whereNull('denied_at');

        $step1 = (clone $base)->count();
        \Log::debug('[Payroll] Step 1 — approved, not draft, not denied', ['count' => $step1]);

        $step2 = (clone $base)
            ->whereRaw('DATE(COALESCE(approved_at, updated_at)) >= ?', [$periodStart])
            ->whereRaw('DATE(COALESCE(approved_at, updated_at)) <= ?', [$periodEnd])
            ->count();
        \Log::debug('[Payroll] Step 2 — after approval date filter', [
            'period_start' => $periodStart,
            'period_end'   => $periodEnd,
            'count'        => $step2,
        ]);

        // Sample approved_at values to verify column state
        $sample = (clone $base)->select(['id','is_approved','approved_at','updated_at'])->limit(5)->get();
        \Log::debug('[Payroll] Step 2b — sample approved recap dates', ['sample' => $sample->toArray()]);

        $step3 = (clone $base)
            ->whereRaw('DATE(COALESCE(approved_at, updated_at)) >= ?', [$periodStart])
            ->whereRaw('DATE(COALESCE(approved_at, updated_at)) <= ?', [$periodEnd])
            ->whereHas('event', fn ($q) => $q->where('event_status', '1'))
            ->count();
        \Log::debug('[Payroll] Step 3 — after event completed filter', ['count' => $step3]);

        $step4 = (clone $base)
            ->whereRaw('DATE(COALESCE(approved_at, updated_at)) >= ?', [$periodStart])
            ->whereRaw('DATE(COALESCE(approved_at, updated_at)) <= ?', [$periodEnd])
            ->whereHas('event', fn ($q) => $q->where('event_status', '1'))
            ->whereNotExists(function ($sub) {
                $sub->from('ambassador_payouts')
                    ->whereColumn('ambassador_payouts.event_id', 'recaps.event_id')
                    ->whereColumn('ambassador_payouts.user_id', 'recaps.ambassador_id')
                    ->whereIn('ambassador_payouts.status', ['queued', 'processing', 'completed']);
            })
            ->count();
        \Log::debug('[Payroll] Step 4 — after payout status exclusion', ['count' => $step4]);

        $step5 = (clone $base)
            ->whereRaw('DATE(COALESCE(approved_at, updated_at)) >= ?', [$periodStart])
            ->whereRaw('DATE(COALESCE(approved_at, updated_at)) <= ?', [$periodEnd])
            ->whereHas('event', fn ($q) => $q->where('event_status', '1'))
            ->whereNotExists(function ($sub) {
                $sub->from('ambassador_payouts')
                    ->whereColumn('ambassador_payouts.event_id', 'recaps.event_id')
                    ->whereColumn('ambassador_payouts.user_id', 'recaps.ambassador_id')
                    ->whereIn('ambassador_payouts.status', ['queued', 'processing', 'completed']);
            })
            ->whereNotExists(function ($sub) {
                $sub->from('payroll_batch_items')
                    ->whereColumn('payroll_batch_items.reference_id', 'recaps.id')
                    ->where('payroll_batch_items.payment_type', 'recap')
                    ->whereIn('payroll_batch_items.status', ['created', 'processing', 'completed', 'failed']);
            })
            ->count();
        \Log::debug('[Payroll] Step 5 — after batch item duplicate check (FINAL)', ['count' => $step5]);
        // ── END DEBUG ────────────────────────────────────────────────────

        // ── 1. Collect eligible recaps ───────────────────────────────────
        $recaps = Recap::query()
            ->with(['event', 'ambassador.ambassadorProfile'])
            ->where('is_draft', false)
            ->where('is_approved', true)
            // Expedited recaps are released by the immediate Dwolla flow at
            // approval and must never be reconsidered by Batch Payroll.
            ->where('is_expedited_requested', false)
            ->whereNull('denied_at')
            ->whereRaw('DATE(COALESCE(approved_at, updated_at)) >= ?', [$periodStart])
            ->whereRaw('DATE(COALESCE(approved_at, updated_at)) <= ?', [$periodEnd])
            ->whereHas('event', fn ($q) => $q->where('event_status', '1'))
            ->whereNotExists(function ($sub) {
                $sub->from('ambassador_payouts')
                    ->whereColumn('ambassador_payouts.event_id', 'recaps.event_id')
                    ->whereColumn('ambassador_payouts.user_id', 'recaps.ambassador_id')
                    ->whereIn('ambassador_payouts.status', [
                        'queued', 'processing', 'completed',
                    ]);
            })
            ->whereNotExists(function ($sub) {
                $sub->from('payroll_batch_items')
                    ->whereColumn('payroll_batch_items.reference_id', 'recaps.id')
                    ->where('payroll_batch_items.payment_type', 'recap')
                    ->whereIn('payroll_batch_items.status', [
                        'created', 'processing', 'completed', 'failed',
                    ]);
            })
            ->get();

        if ($recaps->isEmpty()) {
            throw new \RuntimeException(
                'No eligible approved recaps found for the selected payroll period.'
            );
        }

        // ── 2. Generate batch + items + sync AmbassadorPayout — all atomic ─
        // Amount calculation reuses the same logic as the admin payment table:
        //   base  = Event::getAmbassadorAmount(wage)  (wage × event hours)
        //           or recap.manager_override_amount if the manager set one
        //   bonus = override_pay + pinata_amount from assign_ambassadors
        //
        // For every item added to the batch, the related AmbassadorPayout is
        // updated: approved → queued  (queued_at is set).
        // Only payouts successfully inserted into the batch are updated.
        return DB::transaction(function () use ($recaps, $periodStart, $periodEnd) {

            $items       = [];
            $totalAmount = 0.00;

            foreach ($recaps as $recap) {
                $event        = $recap->event;
                $ambassadorId = (int) $recap->ambassador_id;

                $wage = (float) ($recap->ambassador?->wage ?? 0);

                $assignment = AssignAmbassador::where('event_id', $event->id)
                    ->where('ambassador_id', $ambassadorId)
                    ->first();

                $override = (float) ($assignment->override_pay  ?? 0);
                $pinata   = (float) ($assignment->pinata_amount ?? 0);

                // Manager override on the recap takes precedence over wage calc
                $baseAmount = $recap->manager_override_amount !== null
                    ? (float) $recap->manager_override_amount
                    : (float) $event->getAmbassadorAmount($wage);

                $amount = round($baseAmount + $override + $pinata, 2);

                $totalAmount += $amount;

                $items[] = [
                    'recap'        => $recap,
                    'ambassadorId' => $ambassadorId,
                    'amount'       => $amount,
                ];
            }

            $batchNumber = $this->generateBatchNumber();

            $batch = PayrollBatch::create([
                'batch_number'  => $batchNumber,
                'period_start'  => $periodStart,
                'period_end'    => $periodEnd,
                'status'        => 'created',
                'total_records' => count($items),
                'total_amount'  => round($totalAmount, 2),
                'generated_by'  => Auth::id(),
                'generated_at'  => now(),
            ]);

            foreach ($items as $item) {
                PayrollBatchItem::create([
                    'batch_id'      => $batch->id,
                    'payment_type'  => 'recap',
                    'reference_id'  => $item['recap']->id,
                    'ambassador_id' => $item['ambassadorId'],
                    'amount'        => $item['amount'],
                    'is_expedited'  => (bool) ($item['recap']->is_expedited_requested ?? false),
                    'status'        => 'created',
                    // dwolla_transaction_id and processed_at intentionally omitted
                ]);

                // Sync AmbassadorPayout: approved → queued
                // Only update the payout that belongs to this specific recap's
                // event + ambassador pair and is still in 'approved' status.
                AmbassadorPayout::where('event_id', $item['recap']->event_id)
                    ->where('user_id', $item['ambassadorId'])
                    ->where('status', 'approved')
                    ->update([
                        'status'    => 'queued',
                        'queued_at' => now(),
                    ]);
            }

            return $batch;
        });
    }

    /**
     * Generate a unique, human-readable batch number.
     * Format: BP-YYYYMMDD-NNN  (e.g. BP-20260728-001)
     * The sequence resets per day and pads to 3 digits.
     */
    /**
     * Step-by-step eligibility trace for diagnosing empty payroll batches.
     * Called by PaymentController::generatePayroll() before generate() so the
     * debug output is returned in the 422 error response when no recaps qualify.
     *
     * @return list<string>
     */
    public function debugEligibility(string $periodStart, string $periodEnd): array
    {
        $log = [];

        $step0 = Recap::count();
        $log[] = "STEP 0 | Total recaps in table: {$step0}";

        $step1 = Recap::where('is_draft', false)->count();
        $log[] = "STEP 1 | is_draft=false: {$step1}";

        $step2 = Recap::where('is_draft', false)->where('is_approved', true)->count();
        $log[] = "STEP 2 | + is_approved=true: {$step2}";

        $step3 = Recap::where('is_draft', false)->where('is_approved', true)
            ->whereNull('denied_at')->count();
        $log[] = "STEP 3 | + denied_at IS NULL: {$step3}";

        $nullApprovedAt = Recap::where('is_approved', true)->whereNull('approved_at')->count();
        $log[] = "STEP 3b | Approved recaps with NULL approved_at: {$nullApprovedAt}";

        $step4 = Recap::where('is_draft', false)->where('is_approved', true)
            ->whereNull('denied_at')
            ->whereRaw('DATE(COALESCE(approved_at, updated_at)) >= ?', [$periodStart])
            ->whereRaw('DATE(COALESCE(approved_at, updated_at)) <= ?', [$periodEnd])
            ->count();
        $log[] = "STEP 4 | + approval date in period [{$periodStart} — {$periodEnd}]: {$step4}";

        $dateSample = Recap::where('is_approved', true)
            ->selectRaw('DATE(COALESCE(approved_at, updated_at)) as coalesce_date, COUNT(*) as cnt')
            ->groupByRaw('DATE(COALESCE(approved_at, updated_at))')
            ->orderByRaw('DATE(COALESCE(approved_at, updated_at)) DESC')
            ->limit(10)
            ->get();
        foreach ($dateSample as $row) {
            $log[] = "STEP 4b | coalesce_date={$row->coalesce_date} count={$row->cnt}";
        }

        $step5 = Recap::where('is_draft', false)->where('is_approved', true)
            ->whereNull('denied_at')
            ->whereRaw('DATE(COALESCE(approved_at, updated_at)) >= ?', [$periodStart])
            ->whereRaw('DATE(COALESCE(approved_at, updated_at)) <= ?', [$periodEnd])
            ->whereHas('event', fn ($q) => $q->where('event_status', '1'))
            ->count();
        $log[] = "STEP 5 | + event_status='1' (completed): {$step5}";

        $step6 = Recap::where('is_draft', false)->where('is_approved', true)
            ->whereNull('denied_at')
            ->whereRaw('DATE(COALESCE(approved_at, updated_at)) >= ?', [$periodStart])
            ->whereRaw('DATE(COALESCE(approved_at, updated_at)) <= ?', [$periodEnd])
            ->whereHas('event', fn ($q) => $q->where('event_status', '1'))
            ->whereNotExists(function ($sub) {
                $sub->from('ambassador_payouts')
                    ->whereColumn('ambassador_payouts.event_id', 'recaps.event_id')
                    ->whereColumn('ambassador_payouts.user_id', 'recaps.ambassador_id')
                    ->whereIn('ambassador_payouts.status', ['queued', 'processing', 'completed']);
            })
            ->count();
        $log[] = "STEP 6 | + payout NOT already queued/processing/completed: {$step6}";

        $payoutStatuses = DB::select("
            SELECT ap.status, COUNT(*) as cnt
            FROM recaps r
            LEFT JOIN ambassador_payouts ap
                ON ap.event_id = r.event_id AND ap.user_id = r.ambassador_id
            WHERE r.is_draft = 0 AND r.is_approved = 1 AND r.denied_at IS NULL
              AND DATE(COALESCE(r.approved_at, r.updated_at)) >= ?
              AND DATE(COALESCE(r.approved_at, r.updated_at)) <= ?
            GROUP BY ap.status
        ", [$periodStart, $periodEnd]);
        foreach ($payoutStatuses as $row) {
            $status = $row->status ?? 'NULL (no payout)';
            $log[] = "STEP 6b | payout.status={$status} count={$row->cnt}";
        }

        $step7 = Recap::where('is_draft', false)->where('is_approved', true)
            ->whereNull('denied_at')
            ->whereRaw('DATE(COALESCE(approved_at, updated_at)) >= ?', [$periodStart])
            ->whereRaw('DATE(COALESCE(approved_at, updated_at)) <= ?', [$periodEnd])
            ->whereHas('event', fn ($q) => $q->where('event_status', '1'))
            ->whereNotExists(function ($sub) {
                $sub->from('ambassador_payouts')
                    ->whereColumn('ambassador_payouts.event_id', 'recaps.event_id')
                    ->whereColumn('ambassador_payouts.user_id', 'recaps.ambassador_id')
                    ->whereIn('ambassador_payouts.status', ['queued', 'processing', 'completed']);
            })
            ->whereNotExists(function ($sub) {
                $sub->from('payroll_batch_items')
                    ->whereColumn('payroll_batch_items.reference_id', 'recaps.id')
                    ->where('payroll_batch_items.payment_type', 'recap')
                    ->whereIn('payroll_batch_items.status', ['created', 'processing', 'completed', 'failed']);
            })
            ->count();
        $log[] = "STEP 7 | + no active batch item (FINAL eligible): {$step7}";

        \Illuminate\Support\Facades\Log::info('=== PAYROLL DEBUG TRACE ===', [
            'period' => "{$periodStart} to {$periodEnd}",
            'steps'  => $log,
        ]);

        return $log;
    }

    private function generateBatchNumber(): string
    {
        $prefix = 'BP-' . now()->format('Ymd') . '-';

        $last = PayrollBatch::where('batch_number', 'like', $prefix . '%')
            ->orderByDesc('batch_number')
            ->value('batch_number');

        $seq = $last ? ((int) substr($last, -3)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }
}
