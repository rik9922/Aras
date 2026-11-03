<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Brand;
use App\Models\EventLocation;
use App\Models\Market;
use App\Models\PayrollBatch;
use App\Models\PayrollBatchItem;
use App\Models\Recap;
use App\Models\Reimbursement;
use App\Models\User;
use App\Jobs\ProcessPayrollBatch;
use App\Models\AmbassadorPayout;
use App\Services\PayrollBatchService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    
    public function paymentForcast(Request $request) {

        $locations = EventLocation::select('id', 'address')->get();
        $markets = Market::get();
        $brands = Brand::get();
        $managers = User::where('role_id', 6)->where('status', '1')->get();
        $ambassadors = User::select('id', 'name', 'email')->where('role_id', 5)->orderBy('name')->get();
        
        $graceMinutes = (int) config('attendance.late_checkin_grace_minutes', 10);

        $recapsBaseQuery = Recap::query()
            ->select([
                'recaps.*',
                'aa.override_pay as assignment_override_pay',
                'aa.pinata_amount as assignment_pinata_amount',
                'aa.event_date_time as assignment_event_date_time',
                'aa.is_no_show as assignment_is_no_show',
            ])
            ->leftJoin('assign_ambassadors as aa', function ($join) {
                $join->on('aa.event_id', '=', 'recaps.event_id')
                    ->on('aa.ambassador_id', '=', 'recaps.ambassador_id');
            })
            ->where('recaps.is_draft', false)
            ->whereNotExists(function ($sub) {
                $sub->from('ambassador_payouts')
                    ->whereColumn('ambassador_payouts.event_id', 'recaps.event_id')
                    ->whereColumn('ambassador_payouts.user_id', 'recaps.ambassador_id')
                    ->whereIn('ambassador_payouts.status', ['completed', 'paid']);
            })
            ->whereNotExists(function ($sub) {
                $sub->from('payroll_batch_items')
                    ->whereColumn('payroll_batch_items.reference_id', 'recaps.id')
                    ->where('payroll_batch_items.payment_type', 'recap')
                    ->where('payroll_batch_items.status', 'completed');
            });

        // ── Apply all filters to a single query (used for both GET and AJAX) ──
        $query = clone $recapsBaseQuery;

        if ($request->filled('market_id')) {
            $query->whereHas('event', fn ($q) => $q->where('market_id', $request->market_id));
        }

        if ($request->filled('manager_id')) {
            $query->whereHas('event', fn ($q) => $q->where('manager_id', $request->manager_id));
        }

        if ($request->filled('location')) {
            $query->whereHas('event', fn ($q) => $q->where('event_location_id', $request->location));
        }

        // ambassador_name and ambassador_email both carry a user ID
        if ($request->filled('ambassador_name')) {
            $query->where('recaps.ambassador_id', $request->ambassador_name);
        }

        if ($request->filled('ambassador_email')) {
            $query->where('recaps.ambassador_id', $request->ambassador_email);
        }

        if ($request->filled('brand_id')) {
            $query->whereHas('event', fn ($q) => $q->forBrandOrDescendants($request->brand_id));
        }

        if ($request->filled('year')) {
            $query->whereHas('event', fn ($q) => $q->whereYear('start_time', $request->year));
        }

        if ($request->filled('month')) {
            $query->whereHas('event', fn ($q) => $q->whereMonth('start_time', $request->month));
        }

        // start_day / end_day arrive as full Y-m-d date strings from the form
        if ($request->filled('start_day') && $request->filled('end_day')) {
            $query->whereHas('event', fn ($q) => $q->whereBetween(
                \Illuminate\Support\Facades\DB::raw('DATE(start_time)'),
                [$request->start_day, $request->end_day]
            ));
        } elseif ($request->filled('start_day')) {
            $query->whereHas('event', fn ($q) => $q->whereDate('start_time', '>=', $request->start_day));
        } elseif ($request->filled('end_day')) {
            $query->whereHas('event', fn ($q) => $q->whereDate('start_time', '<=', $request->end_day));
        }

        if ($request->filled('pinata_pay') && $request->pinata_pay == 1) {
            $query->whereHas('event', fn ($q) => $q->where('pinata_pay', 1));
        }

        if ($request->filled('no_show') && $request->no_show == 1) {
            $query->where('aa.is_no_show', 1);
        }

        // Only Events With Recaps — show only submitted (non-draft) recaps (base query already
        // enforces is_draft=false, so this checkbox is effectively a no-op on the base set;
        // kept for UI consistency and future extension).
        // Only Events With No Recaps — would conflict with querying the recaps table; excluded
        // from base dataset filtering per business rule (all events shown by default).

        // Exclude Unpaid Events — hide rows where a completed payout already exists.
        if ($request->filled('exclude_unpaid_events') && $request->exclude_unpaid_events == 1) {
            $query->whereNotExists(function ($sub) {
                $sub->from('ambassador_payouts')
                    ->whereColumn('ambassador_payouts.event_id', 'recaps.event_id')
                    ->whereColumn('ambassador_payouts.user_id', 'recaps.ambassador_id')
                    ->whereIn('ambassador_payouts.status', ['completed', 'paid']);
            });
        }

        $recaps = $query
            ->with(['event.brand', 'event.location', 'event.manager', 'ambassador'])
            ->latest('recaps.id')
            ->get();

        $reimbursements = $this->buildForecastReimbursementQuery($request)->get();
        $recaps = $this->paginateForecastRows(
            $this->buildForecastRows($recaps, $reimbursements),
            $request
        );

        if ($request->ajax()) {
            return response()->json([
                'table_html' => view('admin.payment.partials.forecast-table', compact('recaps', 'graceMinutes'))->render(),
                'pagination' => $recaps->links('vendor.pagination.custom')->toHtml(),
                'total'      => $recaps->total(),
                'status'     => 200,
            ]);
        }

        return view('admin.payment.payment-forecast', compact('recaps', 'locations', 'markets', 'brands', 'managers', 'ambassadors', 'graceMinutes'));
    }

    private function buildForecastReimbursementQuery(Request $request)
    {
        $query = Reimbursement::query()
            ->with(['user', 'event.brand', 'event.location', 'event.manager', 'brand'])
            ->whereIn('status', ['pending', 'approved']);

        if ($request->filled('market_id')) {
            $query->whereHas('event', fn ($q) => $q->where('market_id', $request->market_id));
        }

        if ($request->filled('manager_id')) {
            $query->whereHas('event', fn ($q) => $q->where('manager_id', $request->manager_id));
        }

        if ($request->filled('location')) {
            $query->whereHas('event', fn ($q) => $q->where('event_location_id', $request->location));
        }

        if ($request->filled('ambassador_name')) {
            $query->where('user_id', $request->ambassador_name);
        }

        if ($request->filled('ambassador_email')) {
            $query->where('user_id', $request->ambassador_email);
        }

        if ($request->filled('brand_id')) {
            $brandIds = Brand::descendantIdsIncludingSelfById((int) $request->brand_id);
            $query->where(function ($q) use ($brandIds, $request) {
                $q->whereIn('brand_id', $brandIds)
                    ->orWhereHas('event', fn ($eventQuery) => $eventQuery->forBrandOrDescendants($request->brand_id));
            });
        }

        if ($request->filled('year')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('event', fn ($eventQuery) => $eventQuery->whereYear('start_time', $request->year))
                    ->orWhereYear('expense_date', $request->year);
            });
        }

        if ($request->filled('month')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('event', fn ($eventQuery) => $eventQuery->whereMonth('start_time', $request->month))
                    ->orWhereMonth('expense_date', $request->month);
            });
        }

        if ($request->filled('start_day') && $request->filled('end_day')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('event', fn ($eventQuery) => $eventQuery->whereBetween(
                    DB::raw('DATE(start_time)'),
                    [$request->start_day, $request->end_day]
                ))->orWhereBetween('expense_date', [$request->start_day, $request->end_day]);
            });
        } elseif ($request->filled('start_day')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('event', fn ($eventQuery) => $eventQuery->whereDate('start_time', '>=', $request->start_day))
                    ->orWhereDate('expense_date', '>=', $request->start_day);
            });
        } elseif ($request->filled('end_day')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('event', fn ($eventQuery) => $eventQuery->whereDate('start_time', '<=', $request->end_day))
                    ->orWhereDate('expense_date', '<=', $request->end_day);
            });
        }

        if ($request->filled('pinata_pay') && $request->pinata_pay == 1) {
            $query->whereRaw('1 = 0');
        }

        if ($request->filled('no_show') && $request->no_show == 1) {
            $query->whereRaw('1 = 0');
        }

        if ($request->filled('exclude_unpaid_events') && $request->exclude_unpaid_events == 1) {
            $query->where('status', '!=', 'paid');
        }

        return $query->latest('id');
    }

    private function buildForecastRows($recaps, $reimbursements)
    {
        $batchItems = PayrollBatchItem::query()
            ->where('payment_type', 'recap')
            ->whereIn('reference_id', $recaps->pluck('id')->all())
            ->latest('id')
            ->get()
            ->unique('reference_id')
            ->keyBy('reference_id');

        $payouts = AmbassadorPayout::query()
            ->whereIn('event_id', $recaps->pluck('event_id')->all())
            ->whereIn('user_id', $recaps->pluck('ambassador_id')->all())
            ->get()
            ->keyBy(fn ($payout) => $payout->event_id . ':' . $payout->user_id);

        $recapRows = $recaps->map(function ($recap) use ($batchItems, $payouts) {
            $payout = $payouts->get($recap->event_id . ':' . $recap->ambassador_id);
            $batchItem = $batchItems->get($recap->id);
            $grossAmount = (float) ($payout?->original_amount ?? $this->calculateRecapGrossAmount($recap));
            $fee = (float) ($payout?->expedited_fee ?? ($recap->is_expedited_requested ? 2.50 : 0.00));
            $netAmount = (float) ($payout?->amount ?? round($grossAmount - $fee, 2));
            $paymentType = $recap->is_expedited_requested ? 'event_pay_expedited' : 'event_pay';
            $approvalDate = $recap->approved_at ?? $payout?->approved_at;

            return (object) [
                'source_type' => 'recap',
                'source_id' => $recap->id,
                'event' => $recap->event,
                'ambassador' => $recap->ambassador,
                'ambassador_name' => $this->formatUserName($recap->ambassador),
                'event_name' => $recap->event?->event_name ?? $recap->event?->name ?? 'Event #' . $recap->event_id,
                'payment_type' => $paymentType,
                'payment_type_label' => $paymentType === 'event_pay_expedited' ? 'Event Pay (Expedited)' : 'Event Pay',
                'gross_amount' => $grossAmount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'approval_date' => $approvalDate,
                'payroll_status' => $this->recapPayrollStatus($recap, $batchItem, $payout),
                'status_badge_class' => $this->statusBadgeClass($this->recapPayrollStatus($recap, $batchItem, $payout)),
                'action_url' => route($this->routePrefix() . '.show-recap', $recap->id),
                'action_label' => 'View',
                'sort_date' => $approvalDate ?? $recap->created_at,
                'created_at' => $recap->created_at,
            ];
        });

        $reimbursementRows = $reimbursements->map(function ($reimbursement) {
            $approvalDate = $reimbursement->reviewed_at;
            $grossAmount = (float) $reimbursement->amount;

            return (object) [
                'source_type' => 'reimbursement',
                'source_id' => $reimbursement->id,
                'event' => $reimbursement->event,
                'ambassador' => $reimbursement->user,
                'ambassador_name' => $this->formatUserName($reimbursement->user),
                'event_name' => $reimbursement->event?->event_name
                    ?? $reimbursement->event?->name
                    ?? ($reimbursement->event_id ? 'Event #' . $reimbursement->event_id : '-'),
                'payment_type' => 'reimbursement',
                'payment_type_label' => 'Reimbursement',
                'gross_amount' => $grossAmount,
                'fee' => 0.00,
                'net_amount' => $grossAmount,
                'approval_date' => $approvalDate,
                'payroll_status' => $this->reimbursementPayrollStatus($reimbursement),
                'status_badge_class' => $this->statusBadgeClass($this->reimbursementPayrollStatus($reimbursement)),
                'action_url' => route($this->routePrefix() . '.reimbursements.show', $reimbursement),
                'action_label' => 'View',
                'sort_date' => $approvalDate ?? $reimbursement->created_at,
                'created_at' => $reimbursement->created_at,
            ];
        });

        return $recapRows
            ->concat($reimbursementRows)
            ->sortByDesc(fn ($row) => $this->timestampFor($row->sort_date))
            ->values();
    }

    private function paginateForecastRows($rows, Request $request): LengthAwarePaginator
    {
        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();

        return (new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        ))->withQueryString();
    }

    private function calculateRecapGrossAmount(Recap $recap): float
    {
        $wage = (float) ($recap->ambassador?->wage ?? 0);
        $baseAmount = $recap->manager_override_amount !== null
            ? (float) $recap->manager_override_amount
            : (float) ($recap->event?->getAmbassadorAmount($wage) ?? 0);

        return round(
            $baseAmount
            + (float) ($recap->assignment_override_pay ?? 0)
            + (float) ($recap->assignment_pinata_amount ?? 0),
            2
        );
    }

    private function recapPayrollStatus(Recap $recap, ?PayrollBatchItem $batchItem, ?AmbassadorPayout $payout): string
    {
        if ($batchItem) {
            return ucfirst(str_replace('_', ' ', (string) $batchItem->status));
        }

        if ($payout) {
            return ucfirst(str_replace('_', ' ', (string) $payout->status));
        }

        if (! empty($recap->denied_at) || ! empty($recap->deny_reason)) {
            return 'Rejected';
        }

        return $recap->is_approved ? 'Approved' : 'Pending';
    }

    private function reimbursementPayrollStatus(Reimbursement $reimbursement): string
    {
        return match ($reimbursement->status) {
            'paid' => 'Paid',
            default => ucfirst((string) $reimbursement->status),
        };
    }

    private function statusBadgeClass(string $status): string
    {
        return match (strtolower($status)) {
            'paid', 'completed' => 'bg-success',
            'approved', 'created', 'queued', 'processing' => 'bg-info text-dark',
            'pending' => 'bg-warning text-dark',
            'failed', 'job failed', 'cancelled', 'rejected' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    private function formatUserName(?User $user): string
    {
        if (! $user) {
            return '-';
        }

        $name = trim((string) ($user->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

        return $name !== '' ? $name : '-';
    }

    private function timestampFor($date): int
    {
        if (! $date) {
            return 0;
        }

        return $date instanceof \DateTimeInterface
            ? $date->getTimestamp()
            : Carbon::parse($date)->getTimestamp();
    }

    private function routePrefix(): string
    {
        return (string) (Auth::user()->role_id ?? '') === '6' ? 'manager' : 'admin';
    }

    public function payment(Request $request) {
        $ambassadors = User::select('id', 'first_name', 'last_name', 'name')->where('role_id', 5)->orderBy('name')->get();

        // Data source is now PayrollBatchItem. Status and creation date come
        // directly from the batch item row — no AmbassadorPayout lookup needed.
        $rows = $this->buildPaymentQuery($request)->paginate(20)->withQueryString();

        if ($request->ajax()) {
            $rows = $this->buildPaymentQuery($request)->paginate(20)->withQueryString();

            return response()->json([
                'table_html' => view('admin.payment.partials.payment-table', compact('rows'))->render(),
                'pagination' => $rows->links('vendor.pagination.custom')->toHtml(),
                'total'      => $rows->total(),
                'status'     => 200,
            ]);
        }

        return view('admin.payment.payment', compact('rows', 'ambassadors'));
    }

    /**
     * Returns a flat collection keyed by "event_id:user_id" for O(1) lookup in the view.
     */
    private function loadPayouts($rows): \Illuminate\Support\Collection
    {
        $eventIds     = $rows->pluck('event_id')->unique()->values();
        $ambassadorIds = $rows->pluck('ambassador_id')->unique()->values();

        return \App\Models\AmbassadorPayout::whereIn('event_id', $eventIds)
            ->whereIn('user_id', $ambassadorIds)
            ->get()
            ->keyBy(fn($p) => $p->event_id . ':' . $p->user_id);
    }

    private function buildPaymentQuery(Request $request)
    {
        // Data source: PayrollBatchItem (recap type only).
        // Status and creation date come from the batch item, not AmbassadorPayout.
        $query = \App\Models\PayrollBatchItem::query()
            ->where('payment_type', 'recap')
            ->with([
                'ambassador',
                'recap.event.brand',
                'recap.event.location',
            ]);

        // Date range: filter by event start_time (same search behaviour as before)
        if ($request->filled('startDate')) {
            $query->whereHas('recap.event', fn ($q) => $q->whereDate('start_time', '>=', $request->startDate));
        }
        if ($request->filled('endDate')) {
            $query->whereHas('recap.event', fn ($q) => $q->whereDate('start_time', '<=', $request->endDate));
        }

        // Ambassador filter
        if ($request->filled('ambassador_id')) {
            $query->where('ambassador_id', $request->ambassador_id);
        }

        // Status filter: maps directly to payroll_batch_items.status.
        // UI sends 'error'; DB enum uses 'failed' — normalise before querying.
        if ($request->filled('statuses')) {
            $statuses = collect((array) $request->statuses)
                ->map(fn ($s) => $s === 'error' ? 'failed' : $s)
                ->reject(fn ($s) => $s === 'all')
                ->values()
                ->all();

            if (! empty($statuses)) {
                $query->whereIn('status', $statuses);
            }
        }

        return $query->orderByDesc('id');
    }

    /**
     * Generate a payroll batch for the given date range.
     * Returns JSON with batch summary for the UI to display.
     */
    public function generatePayroll(Request $request, PayrollBatchService $service)
    {
        $request->validate([
            'generate_start_date' => 'required|date',
            'generate_end_date'   => 'required|date|after_or_equal:generate_start_date',
        ]);

        try {
            $batch = $service->generate(
                $request->generate_start_date,
                $request->generate_end_date
            );

            return response()->json([
                'status'        => 'success',
                'batch_number'  => $batch->batch_number,
                'period_start'  => $batch->period_start->format('m/d/Y'),
                'period_end'    => $batch->period_end->format('m/d/Y'),
                'total_records' => $batch->total_records,
                'total_amount'  => number_format($batch->total_amount, 2),
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Payroll batch generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Payroll generation failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Process all payroll batch items in the 'created' status.
     * Dispatches a queued job so the HTTP response is immediate.
     */
    public function processPayroll(Request $request)
    {
        // Find batches that have at least one 'created' item
        $batchIds = PayrollBatchItem::where('status', 'created')
            ->distinct()
            ->pluck('batch_id');

        if ($batchIds->isEmpty()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No payments in Created status found to process.',
            ], 422);
        }

        $userId = Auth::id();
        $count  = 0;

        foreach ($batchIds as $batchId) {
            ProcessPayrollBatch::dispatch($batchId, $userId)->onQueue('default');
            $count++;
        }

        Log::info('[ProcessPayroll] Dispatched batch jobs', [
            'batch_ids' => $batchIds->toArray(),
            'user_id'   => $userId,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Payroll processing started for ' . $count . ' batch(es).',
        ]);
    }
}
