<?php

namespace App\Services\Forecast;

use App\Models\AmbassadorPayout;
use App\Models\Reimbursement;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class PayrollForecastService
{
    /**
     * Forecast outgoing payout obligations for the next N days.
     *
     * Includes:
     * - Ambassador payouts in approved/queued/processing states
     * - Reimbursements in pending/approved states
     */
    public function forecastForNextDays(int $days = 14): array
    {
        $days = max(1, $days);
        $windowStart = CarbonImmutable::now();
        $windowEnd = $windowStart->addDays($days);

        $payoutStatuses = ['approved', 'queued', 'processing'];
        $reimbursementStatuses = ['pending', 'approved'];

        $payoutRows = AmbassadorPayout::query()
            ->select([
                'status',
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count'),
            ])
            ->whereIn('status', $payoutStatuses)
            ->whereBetween(
                DB::raw('COALESCE(approved_at, queued_at, processing_at, created_at)'),
                [$windowStart, $windowEnd]
            )
            ->groupBy('status')
            ->get();

        $reimbursementRows = Reimbursement::query()
            ->select([
                'status',
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count'),
            ])
            ->whereIn('status', $reimbursementStatuses)
            ->whereBetween(
                DB::raw('COALESCE(reviewed_at, created_at)'),
                [$windowStart, $windowEnd]
            )
            ->groupBy('status')
            ->get();

        $payoutBreakdown = [];
        $payoutTotal = 0.0;
        $payoutCount = 0;
        foreach ($payoutRows as $row) {
            $amount = (float) ($row->total ?? 0);
            $count = (int) ($row->count ?? 0);
            $payoutBreakdown[$row->status] = [
                'count' => $count,
                'amount' => $amount,
            ];
            $payoutTotal += $amount;
            $payoutCount += $count;
        }

        $reimbursementBreakdown = [];
        $reimbursementTotal = 0.0;
        $reimbursementCount = 0;
        foreach ($reimbursementRows as $row) {
            $amount = (float) ($row->total ?? 0);
            $count = (int) ($row->count ?? 0);
            $reimbursementBreakdown[$row->status] = [
                'count' => $count,
                'amount' => $amount,
            ];
            $reimbursementTotal += $amount;
            $reimbursementCount += $count;
        }

        return [
            'window_start' => $windowStart->toDateTimeString(),
            'window_end' => $windowEnd->toDateTimeString(),
            'days' => $days,
            'currency' => 'USD',
            'ambassador_payouts' => [
                'statuses' => $payoutStatuses,
                'count' => $payoutCount,
                'total' => round($payoutTotal, 2),
                'breakdown_by_status' => $payoutBreakdown,
            ],
            'reimbursements' => [
                'statuses' => $reimbursementStatuses,
                'count' => $reimbursementCount,
                'total' => round($reimbursementTotal, 2),
                'breakdown_by_status' => $reimbursementBreakdown,
            ],
            'grand_total' => round($payoutTotal + $reimbursementTotal, 2),
            'grand_count' => $payoutCount + $reimbursementCount,
        ];
    }
}
