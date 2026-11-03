<?php

namespace App\Jobs;

use App\Models\Reimbursement;
use App\Models\UserTransaction;
use App\Services\Dwolla\DwollaTransferService;
use Illuminate\Support\Facades\DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InitiateReimbursementPayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public Reimbursement $reimbursement) {}

    public function handle(DwollaTransferService $dwolla)
    {
        $reimbursementLog = Log::channel('reimbursement_pay');
        $reimbursement = $this->reimbursement->fresh(['user.dwollaCustomer.verifiedDefaultFundingSource']);

        if ($reimbursement->status !== 'approved' || $reimbursement->dwolla_transfer_id) {
            $reimbursementLog->warning('Reimbursement payout job skipped by idempotency guard.', [
                'reimbursement_id' => $reimbursement->id,
                'user_id' => $reimbursement->user_id,
                'amount' => $reimbursement->amount,
                'status' => $reimbursement->status,
                'dwolla_transfer_id' => $reimbursement->dwolla_transfer_id,
            ]);

            return;
        }

        if (! $reimbursement->user?->hasVerifiedBankAccount()) {
            $reimbursementLog->warning('Reimbursement payout skipped: ambassador bank account not verified.', [
                'reimbursement_id' => $reimbursement->id,
                'user_id' => $reimbursement->user_id,
                'amount' => $reimbursement->amount,
            ]);

            return;
        }

        $reimbursementLog->info('Reimbursement payout job started.', [
            'reimbursement_id' => $reimbursement->id,
            'user_id' => $reimbursement->user_id,
            'amount' => $reimbursement->amount,
            'status' => $reimbursement->status,
        ]);

        try {
            DB::transaction(function () use ($dwolla, $reimbursement, $reimbursementLog) {
                $fresh = $reimbursement->fresh(['user.dwollaCustomer.verifiedDefaultFundingSource']);
                $fundingSource = $fresh->user->dwollaCustomer?->ensureVerifiedDefaultFundingSource();

                $transferId = $dwolla->createForReimbursement($fresh);
                $fresh->update([
                    'dwolla_transfer_id' => $transferId,
                    'dwolla_funding_source_id' => $fundingSource?->id,
                ]);

                $reimbursementLog->info('Reimbursement Dwolla transfer created.', [
                    'reimbursement_id' => $fresh->id,
                    'user_id' => $fresh->user_id,
                    'amount' => $fresh->amount,
                    'status' => $fresh->status,
                    'dwolla_transfer_id' => $transferId,
                ]);

                // Do NOT sync reimbursements to QuickBooks (Issue #11).
                // Webhook processing will set status=paid/paid_at when Dwolla completes.
                // We still upsert a transaction record now so finance can see it immediately.
                $bankName = $fundingSource->bank_name ?? null;
                $accountLast4 = $fundingSource->account_number ? substr($fundingSource->account_number, -4) : null;

                UserTransaction::updateOrCreate(
                    [
                        'user_id' => $fresh->user_id,
                        'type' => 'reimbursement',
                        'source_id' => $fresh->id,
                        'source_type' => Reimbursement::class,
                    ],
                    [
                        'amount' => $fresh->amount,
                        'currency' => 'USD',
                        'status' => $fresh->status, // pending/approved/paid via webhook
                        'dwolla_transfer_id' => $fresh->dwolla_transfer_id,
                        'description' => $fresh->description_for_quickbooks,
                        'occurred_at' => $fresh->updated_at ?? now(),
                        'meta' => [
                            'event_id' => $fresh->event_id,
                            'brand_id' => $fresh->brand_id,
                            'bank_name' => $bankName,
                            'account_last4' => $accountLast4,
                        ],
                    ]
                );
            });
        } catch (\Exception $e) {
            $reimbursementLog->error('Reimbursement payout failed.', [
                'reimbursement_id' => $reimbursement->id,
                'user_id' => $reimbursement->user_id,
                'amount' => $reimbursement->amount,
                'status' => $reimbursement->status,
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $e)
    {
        Log::channel('reimbursement_pay')->error('Reimbursement payout job failed.', [
            'reimbursement_id' => $this->reimbursement->id,
            'user_id' => $this->reimbursement->user_id,
            'amount' => $this->reimbursement->amount,
            'exception_class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
