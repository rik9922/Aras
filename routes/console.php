<?php

use App\Models\Event;
use App\Models\Recap;
use App\Models\RecapDeductionLog;
use App\Services\NotificationDispatchService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Artisan::command('recaps:enforce-deadlines', function () {
//     $now = now();

//     $acquireDailyRecapOverdueNotificationSlot = static function (
//         int $eventId,
//         int $recipientId,
//         string $recipientRole
//     ) use ($now): bool {
//         $notifiedDate = $now->toDateString();

//         if (Schema::hasTable('recap_overdue_notification_marks')) {
//             return DB::table('recap_overdue_notification_marks')->insertOrIgnore([
//                 'event_id' => $eventId,
//                 'recipient_id' => $recipientId,
//                 'recipient_role' => $recipientRole,
//                 'notified_date' => $notifiedDate,
//                 'created_at' => $now,
//                 'updated_at' => $now,
//             ]) > 0;
//         }

//         $fallbackKey = sprintf(
//             'missing-recap-%s-%s-%s-%s',
//             $recipientRole,
//             $eventId,
//             $recipientId,
//             $notifiedDate
//         );
//         $secondsUntilEndOfDay = max(1, (int) $now->diffInSeconds($now->copy()->endOfDay()));

//         return cache()->add($fallbackKey, true, $secondsUntilEndOfDay);
//     };

//     // 1) Events with missing recaps more than 48 hours after end_time
//     $missingCutoff = $now->copy()->subHours(48);

//     $events = Event::with(['manager'])
//         ->where('recap_needed', 1)
//         ->whereIn('event_status', ['0', '1'])
//         ->whereNotNull('end_time')
//         ->where('end_time', '<=', $missingCutoff)
//         ->whereDoesntHave('recap')
//         ->get();

//     foreach ($events as $event) {
//         $alreadyOverdue = $event->recap_status === 'overdue'
//             && (float) $event->recap_payout_amount === 0.0;

//         // Apply overdue payout/status only once per event.
//         if (! $alreadyOverdue) {
//             $event->update([
//                 'recap_status' => 'overdue',
//                 'recap_payout_amount' => 0,
//             ]);

//             RecapDeductionLog::create([
//                 'event_id' => $event->id,
//                 'recap_id' => null,
//                 'ambassador_id' => null,
//                 'reason' => 'missing_recap_after_48_hours',
//                 'meta' => json_encode([
//                     'end_time' => $event->end_time,
//                 ]),
//             ]);
//         }

//         // Notify the Market Manager (if assigned) at most once per day per missing recap.
//         if ($event->manager && $event->manager->email) {
//             if ($acquireDailyRecapOverdueNotificationSlot((int) $event->id, (int) $event->manager->id, 'manager')) {
//                 Mail::to($event->manager->email)->send(new \App\Mail\RecapOverdueManagerNotification($event));
//             }
//         }

//         // Notify assigned Brand Ambassadors once per day for their own missing recap.
//         $ambassadors = $event->ambassador()->get();
//         if ($ambassadors->isNotEmpty()) {
//             $notificationDispatch = app(NotificationDispatchService::class);

//             foreach ($ambassadors as $ambassador) {
//                 if (! $acquireDailyRecapOverdueNotificationSlot((int) $event->id, (int) $ambassador->id, 'ambassador')) {
//                     continue;
//                 }

//                 $title = 'Recap overdue';
//                 $message = sprintf(
//                     'Your recap for event #%d is still missing. Please submit it as soon as possible.',
//                     $event->id
//                 );
//                 $emailContentHtml = sprintf(
//                     '<p>Your recap for event <strong>%s</strong> (ID: %d) is still missing and overdue. Please submit your recap as soon as possible.</p><p>Thank you.</p>',
//                     e($event->event_name ?? 'N/A'),
//                     $event->id
//                 );

//                 $notificationDispatch->dispatch(
//                     null,
//                     (int) $ambassador->id,
//                     'missing_recap_overdue',
//                     $title,
//                     $message,
//                     $emailContentHtml,
//                     (int) $event->id
//                 );
//             }
//         }
//     }

//     // 2) Recaps that were rejected more than 24 hours ago and not updated since
//     $rejectedCutoff = $now->copy()->subHours(24);

//     $recaps = Recap::with(['event.manager', 'ambassador'])
//         ->whereNotNull('denied_at')
//         ->where('denied_at', '<=', $rejectedCutoff)
//         ->where(function ($query) {
//             $query->whereNull('status')
//                 ->orWhere('status', '!=', 'auto_zeroed');
//         })
//         // Not resubmitted: last update was at or within 1 min of denial (no edit after denial)
//         ->whereRaw('updated_at <= DATE_ADD(denied_at, INTERVAL 1 MINUTE)')
//         ->get();

//     foreach ($recaps as $recap) {
//         $recap->update([
//             'status' => 'auto_zeroed',
//             'auto_deducted_at' => $now,
//             'auto_deduction_reason' => 'rejected_not_resubmitted_within_24_hours',
//         ]);

//         if ($recap->event) {
//             $recap->event->update([
//                 'recap_status' => 'auto_zeroed',
//                 'recap_payout_amount' => 0,
//             ]);
//         }

//         RecapDeductionLog::create([
//             'event_id' => $recap->event_id,
//             'recap_id' => $recap->id,
//             'ambassador_id' => $recap->ambassador_id,
//             'reason' => 'rejected_not_resubmitted_within_24_hours',
//             'meta' => json_encode([
//                 'denied_at' => $recap->denied_at,
//             ]),
//         ]);

//         // Notify Market Manager if available
//         if ($recap->event && $recap->event->manager && $recap->event->manager->email) {
//             Mail::to($recap->event->manager->email)->send(new \App\Mail\RecapOverdueManagerNotification($recap->event, $recap));
//         }
//     }
// })->purpose('Enforce recap deadlines, auto-deduct payouts, and notify managers')->everyTenMinutes()->withoutOverlapping();

Schedule::command('dwolla:check-operating-balance')
    ->dailyAt('08:00');

Schedule::command('ambassador-first-shift:notify-managers')
    ->dailyAt('08:00');

Schedule::command('recaps:notify-daily-summary')
    ->dailyAt('08:00')
    ->withoutOverlapping();

// Invoice readiness milestones (recaps complete / no upcoming events)
Schedule::command('invoices:notify-month-milestones')
    ->dailyAt('08:10');

Schedule::command('events:notify-late-check-ins')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('events:notify-check-in-discrepancy-digest')
    ->dailyAt('18:00')
    ->withoutOverlapping();

Schedule::command('events:notify-check-in-ready')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('banking:send-setup-reminders')
    ->dailyAt('09:00');
