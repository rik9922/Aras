<?php

namespace App\Services;

use App\Mail\RecapDailySummaryManagerMail;
use App\Models\Recap;
use App\Models\RecapDailySummaryMark;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class RecapDailySummaryNotificationService
{
    /**
     * @return array{ok:bool,recap_count:int,managers_notified:int,skipped:bool,window_start:string,window_end:string}
     */
    public function run(?Carbon $asOf = null, bool $dryRun = false): array
    {
        $asOf = $asOf ?? now();
        $windowStart = $asOf->copy()->subDay();
        $windowEnd = $asOf->copy();

        $pendingRecaps = $this->pendingRecaps($windowStart, $windowEnd);
        $recapCount = $pendingRecaps->count();

        if ($recapCount === 0) {
            return [
                'ok' => true,
                'recap_count' => 0,
                'managers_notified' => 0,
                'skipped' => true,
                'window_start' => $windowStart->toDateTimeString(),
                'window_end' => $windowEnd->toDateTimeString(),
            ];
        }

        $managers = User::query()
            ->where('role_id', 6)
            ->where('status', '1')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get(['id', 'name', 'first_name', 'email']);

        if ($managers->isEmpty()) {
            Log::warning('Recap daily summary skipped: no active managers with email');

            return [
                'ok' => true,
                'recap_count' => $recapCount,
                'managers_notified' => 0,
                'skipped' => true,
                'window_start' => $windowStart->toDateTimeString(),
                'window_end' => $windowEnd->toDateTimeString(),
            ];
        }

        if ($dryRun) {
            return [
                'ok' => true,
                'recap_count' => $recapCount,
                'managers_notified' => $managers->count(),
                'skipped' => false,
                'window_start' => $windowStart->toDateTimeString(),
                'window_end' => $windowEnd->toDateTimeString(),
            ];
        }

        $managersNotified = 0;

        DB::transaction(function () use ($pendingRecaps, $managers, $recapCount, $asOf, &$managersNotified) {
            foreach ($managers as $manager) {
                try {
                    Mail::to($manager->email)->send(new RecapDailySummaryManagerMail($manager, $recapCount));
                    $managersNotified++;
                } catch (\Throwable $e) {
                    Log::error('Recap daily summary email failed', [
                        'manager_id' => $manager->id,
                        'email' => $manager->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (Schema::hasTable('recap_daily_summary_marks')) {
                foreach ($pendingRecaps as $recap) {
                    RecapDailySummaryMark::query()->updateOrCreate(
                        ['recap_id' => $recap->id],
                        [
                            'last_notified_updated_at' => $recap->updated_at,
                            'summary_date' => $asOf->toDateString(),
                        ]
                    );
                }
            }
        });

        return [
            'ok' => true,
            'recap_count' => $recapCount,
            'managers_notified' => $managersNotified,
            'skipped' => false,
            'window_start' => $windowStart->toDateTimeString(),
            'window_end' => $windowEnd->toDateTimeString(),
        ];
    }

    private function pendingRecaps(Carbon $windowStart, Carbon $windowEnd)
    {
        $query = Recap::query()
            ->where('is_draft', false)
            ->where('updated_at', '>=', $windowStart)
            ->where('updated_at', '<=', $windowEnd);

        if (Schema::hasTable('recap_daily_summary_marks')) {
            $query->where(function ($builder) {
                $builder->whereDoesntHave('dailySummaryMark')
                    ->orWhereHas('dailySummaryMark', function ($markQuery) {
                        $markQuery->whereColumn(
                            'recaps.updated_at',
                            '>',
                            'recap_daily_summary_marks.last_notified_updated_at'
                        );
                    });
            });
        }

        return $query->get(['id', 'updated_at']);
    }
}
