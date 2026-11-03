<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Vendor1099ReportController extends Controller
{
    public function index(Request $request)
    {
        $ambassadors = User::where('role_id', 5)
            ->with('w9Form')
            ->orderBy('last_name')
            ->get();

        $expenseTotals = Expense::query()
            ->select('user_id', DB::raw('SUM(total_amount) as total_amount'))
            ->whereIn('user_id', $ambassadors->pluck('id'))
            ->whereNotNull('payout_id')
            ->whereNull('reimbursement_id')
            ->whereHas('payout', fn ($query) => $query->whereIn('status', ['completed', 'paid']))
            ->groupBy('user_id')
            ->pluck('total_amount', 'user_id');

        $ambassadors->each(function (User $ambassador) use ($expenseTotals) {
            $ambassador->reportable_expenses_total = (float) ($expenseTotals->get($ambassador->id) ?? 0);
        });

        return view('admin.vendor-1099-report.index', compact('ambassadors'));
    }

    public function expenses(Request $request, User $user)
    {
        $this->authorizeAmbassador($user);

        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $query = $this->reportableExpenseQuery($user, $filters);
        $filteredTotal = (clone $query)->sum('total_amount');
        $expenses = $query->paginate(20)->withQueryString();

        return view('admin.vendor-1099-report.expenses', compact('user', 'expenses', 'filteredTotal', 'filters'));
    }

    public function exportExpenses(Request $request, User $user): StreamedResponse
    {
        $this->authorizeAmbassador($user);

        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $query = $this->reportableExpenseQuery($user, $filters);
        $filename = 'vendor_1099_expenses_'.$user->id.'_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Amount', 'Currency', 'Payment Type', 'Payment Method', 'Status', 'Reference', 'Event ID', 'Event', 'Description', 'Private Note']);

            $query->chunk(500, function ($expenses) use ($out) {
                foreach ($expenses as $expense) {
                    $payout = $expense->payout;
                    $event = $payout?->event;

                    fputcsv($out, [
                        optional($expense->txn_date)->format('m/d/Y'),
                        number_format((float) $expense->total_amount, 2, '.', ''),
                        $expense->currency_code,
                        'Event Pay',
                        $expense->payment_type,
                        $payout?->status,
                        $payout?->reference,
                        $event?->id,
                        $event?->brand?->brand_name ?? $event?->event_name,
                        $expense->description,
                        $expense->private_note,
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function toggle1099Tracked(Request $request, User $user)
    {
        $this->authorizeAmbassador($user);

        $w9Form = $user->w9Form;

        if (! $w9Form) {
            if ($request->ajax()) {
                return response()->json(['error' => 'W-9 form not found for this ambassador.'], 422);
            }

            return redirect()->back()->with('error', 'W-9 form not found for this ambassador.');
        }

        $w9Form->update([
            'is_1099_vendor' => ! $w9Form->is_1099_vendor,
        ]);

        if ($request->ajax()) {
            return response()->json(['tracked' => $w9Form->is_1099_vendor]);
        }

        return redirect()->back()->with('success', 'Updated.');
    }

    private function reportableExpenseQuery(User $user, array $filters = [])
    {
        $query = Expense::query()
            ->with(['payout.event.brand'])
            ->where('user_id', $user->id)
            ->whereNotNull('payout_id')
            ->whereNull('reimbursement_id')
            ->whereHas('payout', fn ($payoutQuery) => $payoutQuery->whereIn('status', ['completed', 'paid']));

        if (! empty($filters['start_date'])) {
            $query->whereDate('txn_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('txn_date', '<=', $filters['end_date']);
        }

        return $query
            ->orderByDesc('txn_date')
            ->orderByDesc('id');
    }

    private function authorizeAmbassador(User $user): void
    {
        if ($user->role_id !== 5) {
            abort(403);
        }
    }
}
