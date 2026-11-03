<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\InitiateReimbursementPayout;
use App\Models\Reimbursement;
use App\Services\BankSetupNotificationService;
use App\Services\Dwolla\DwollaTransferService;
use App\Services\NotificationDispatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReimbursementController extends Controller
{
    private const DUPLICATE_LOOKBACK_DAYS = 90;

    public function index(Request $request)
    {
        $query = Reimbursement::with('user', 'event', 'brand', 'reviewer');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $reimbursements = $query->orderBy('created_at', 'desc')->paginate(20);

        $showRoute = request()->routeIs('manager.*') ? 'manager.reimbursements.show' : 'admin.reimbursements.show';

        return view('admin.reimbursement.index', compact('reimbursements', 'showRoute'));
    }

    public function show(Reimbursement $reimbursement)
    {
        $reimbursement->load('user', 'event', 'brand', 'reviewer');
        $potentialDuplicates = $this->getPotentialDuplicates($reimbursement);

        return view('admin.reimbursement.show', compact('reimbursement', 'potentialDuplicates'));
    }

    public function approve(Request $request, Reimbursement $reimbursement)
    {
        $reimbursementLog = Log::channel('reimbursement_pay');

        $reimbursementLog->info('Reimbursement approval started.', [
            'reimbursement_id' => $reimbursement->id,
            'user_id' => $reimbursement->user_id,
            'amount' => $reimbursement->amount,
            'status' => $reimbursement->status,
            'reviewer_id' => Auth::id(),
        ]);

        if ($reimbursement->status !== 'pending') {
            $reimbursementLog->warning('Reimbursement approval stopped because status is not pending.', [
                'reimbursement_id' => $reimbursement->id,
                'user_id' => $reimbursement->user_id,
                'status' => $reimbursement->status,
            ]);

            return redirect()->back()->with('error', 'This reimbursement has already been processed.');
        }

        $potentialDuplicates = $this->getPotentialDuplicates($reimbursement);
        if ($potentialDuplicates->isNotEmpty() && ! $request->boolean('override_duplicate_check')) {
            $reimbursementLog->warning('Reimbursement approval stopped because potential duplicates require override.', [
                'reimbursement_id' => $reimbursement->id,
                'user_id' => $reimbursement->user_id,
                'duplicate_count' => $potentialDuplicates->count(),
            ]);

            return redirect()->back()->with('error', 'Potential duplicate reimbursement(s) found. Please review and confirm before approving.');
        }

        $ambassador = $reimbursement->user;
        if (! $ambassador || ! $ambassador->hasVerifiedBankAccount()) {
            $name = $ambassador?->name ?? 'Ambassador';

            $reimbursementLog->warning('Reimbursement approval stopped because ambassador bank account is not verified.', [
                'reimbursement_id' => $reimbursement->id,
                'user_id' => $reimbursement->user_id,
                'ambassador_found' => (bool) $ambassador,
            ]);

            if ($ambassador) {
                app(BankSetupNotificationService::class)->sendPaymentSkippedNotification(
                    $ambassador,
                    Auth::id()
                );
            }

            return redirect()->back()->with(
                'warning',
                "{$name} was skipped because Direct Deposit setup is incomplete."
            );
        }

        $reimbursement->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        $reimbursement = $reimbursement->fresh();

        $reimbursementLog->info('Reimbursement approved; dispatching immediate Dwolla payout.', [
            'reimbursement_id' => $reimbursement->id,
            'user_id' => $reimbursement->user_id,
            'amount' => $reimbursement->amount,
            'status' => $reimbursement->status,
            'queue' => 'payouts',
        ]);

        // Reimbursements are paid immediately and should run with payout workers.
        InitiateReimbursementPayout::dispatch($reimbursement)->onQueue('payouts');

        return redirect()->back()->with('success', 'Reimbursement approved. Payment will be processed shortly.');
    }

    public function reject(Request $request, Reimbursement $reimbursement)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        if ($reimbursement->status !== 'pending') {
            return redirect()->back()->with('error', 'This reimbursement has already been processed.');
        }

        $reimbursement->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        $ba = $reimbursement->user;
        if ($ba && $ba->email) {
            $content = 'Your reimbursement request has been <strong>rejected</strong>.'
                .'<br><br>'
                .'<b>Reimbursement details:</b><br>'
                .'Amount: $'.number_format((float) $reimbursement->amount, 2).' &nbsp;|&nbsp; Date: '.$reimbursement->expense_date->format('M d, Y')
                .'<br><br>'
                .'<b>Reason for rejection:</b><br>'.nl2br(e($request->rejection_reason))
                .'<br><br>'
                .'If you have questions, please reach out to your manager or admin.';

            $plainMessage = trim(preg_replace('/\s+/', ' ', strip_tags((string) $content)));
            $plainMessage = mb_substr($plainMessage, 0, 200);

            app(NotificationDispatchService::class)->dispatch(
                Auth::id(),
                $ba->id,
                'reimbursement',
                'Reimbursement Request Rejected',
                $plainMessage,
                $content,
                (int) $reimbursement->id
            );
        }

        return redirect()->back()->with('success', 'Reimbursement rejected.');
    }

    private function getPotentialDuplicates(Reimbursement $reimbursement)
    {
        return Reimbursement::with('event.brand', 'reviewer')
            ->where('id', '!=', $reimbursement->id)
            ->where('user_id', $reimbursement->user_id)
            ->where('status', '!=', 'rejected')
            ->where('created_at', '>=', now()->subDays(self::DUPLICATE_LOOKBACK_DAYS))
            ->where(function ($query) use ($reimbursement) {
                $query->where('amount', $reimbursement->amount);

                if (! empty($reimbursement->event_id)) {
                    $query->orWhere('event_id', $reimbursement->event_id);
                }
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
    }
}
