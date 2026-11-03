<?php

namespace App\Jobs;

use App\Mail\AmbassadorInvoiceMail;
use App\Models\AmbassadorPayout;
use App\Services\QuickBooksService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CreateInvoiceForPayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public AmbassadorPayout $payout) {}

    public function handle(QuickBooksService $qbService)
    {
        $payout = $this->payout->fresh(['user', 'event.brand', 'fundingSource', 'invoice']);

        // Idempotency: skip if a QuickBooks expense record already exists for this payout.
        // Note: expenses are stored in the expenses table via payout_id (not ambassador_payouts.invoice_id).
        if (! $payout || $payout->expense()->exists()) {
            return;
        }

        // This actually creates a QuickBooks Expense,
        $invoiceId = $qbService->createExpenseForPayout($payout);

        if ($payout->status === 'completed' && $payout->user->email) {
            Mail::to($payout->user->email)->send(new AmbassadorInvoiceMail($payout));
        }
    }

    public function failed(\Throwable $e)
    {
        Log::error('Invoice creation failed for payout: '.$this->payout->id, [
            'error' => $e->getMessage(),
        ]);
    }
}
