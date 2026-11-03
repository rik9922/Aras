<?php

/**
 * Recap deadline enforcement – Tinker test script
 *
 * Run from project root:
 *   php artisan tinker
 *   include 'scripts/test_recap_deadlines_tinker.php';
 */

use App\Models\Event;
use App\Models\Recap;
use App\Models\RecapDeductionLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

echo "=== Recap deadline enforcement – Tinker tests ===\n\n";

// ---------------------------------------------------------------------------
// 1) Test: Missing recap after 48 hours
// ---------------------------------------------------------------------------
$manager = User::whereIn('role_id', [1, 6])->first();
if (!$manager) {
    echo "Skip: No manager user (role_id 1 or 6) found. Create one to test manager notification.\n";
    $managerId = null;
} else {
    $managerId = $manager->id;
    echo "Using manager: {$manager->name} (id: {$managerId}, email: {$manager->email})\n";
}

$endTimeOver48h = Carbon::now()->subHours(50);

$eventNoRecap = Event::create([
    'event_name'       => 'Tinker test – no recap ' . now()->format('Y-m-d H:i'),
    'recap_needed'     => 1,
    'event_status'     => '1',
    'start_time'       => $endTimeOver48h->copy()->subHours(3),
    'end_time'         => $endTimeOver48h,
    'booked_date'      => $endTimeOver48h->toDateString(),
    'manager_id'       => $managerId,
    'timezone'         => 'UTC',
]);

echo "Created test event (no recap) id: {$eventNoRecap->id}, end_time: {$eventNoRecap->end_time}\n";

$logsBefore = RecapDeductionLog::count();
Artisan::call('recaps:enforce-deadlines');
echo "Ran: recaps:enforce-deadlines\n";

$eventNoRecap->refresh();
echo "\nAfter command – Event #{$eventNoRecap->id}:\n";
echo "  recap_status: " . ($eventNoRecap->recap_status ?? 'null') . "\n";
echo "  recap_payout_amount: " . ($eventNoRecap->recap_payout_amount ?? 'null') . "\n";

$newLogs = RecapDeductionLog::where('event_id', $eventNoRecap->id)->get();
echo "  deduction logs: " . $newLogs->count() . " (reason: missing_recap_after_48_hours)\n";
foreach ($newLogs as $log) {
    echo "    - id: {$log->id}, reason: {$log->reason}\n";
}

$logsAfter = RecapDeductionLog::count();
echo "\nTotal RecapDeductionLog count: {$logsBefore} -> {$logsAfter}\n";

// ---------------------------------------------------------------------------
// 2) Test: Rejected recap not resubmitted within 24 hours
// ---------------------------------------------------------------------------
echo "\n--- Rejected recap (not resubmitted in 24h) ---\n";

$ambassador = User::where('role_id', 5)->first();
if (!$ambassador) {
    echo "Skip: No ambassador (role_id 5) found. Create one to test rejected-recap flow.\n";
} else {
    $eventWithRecap = Event::create([
        'event_name'   => 'Tinker test – rejected recap ' . now()->format('Y-m-d H:i'),
        'recap_needed' => 1,
        'event_status' => '1',
        'start_time'   => Carbon::now()->subHours(30),
        'end_time'     => Carbon::now()->subHours(28),
        'booked_date'  => Carbon::now()->toDateString(),
        'manager_id'   => $managerId,
        'timezone'     => 'UTC',
    ]);

    $deniedAt = Carbon::now()->subHours(25);
    $recap = Recap::create([
        'event_id'     => $eventWithRecap->id,
        'ambassador_id' => $ambassador->id,
        'close_time'   => $deniedAt,
        'traffic_location' => 'Test',
        'check_in_with_person' => 'Test',
        'interaction_gender' => 'Test',
        'interaction_age' => 'Test',
        'total_interaction' => 'Test',
        'state_id'      => \App\Models\State::first()?->id,
        'is_approved'   => 0,
        'deny_reason'   => 'Tinker test rejection',
        'denied_at'     => $deniedAt,
    ]);
    // Simulate "not resubmitted": last update at denial time
    DB::table('recaps')->where('id', $recap->id)->update(['updated_at' => $deniedAt]);

    echo "Created event #{$eventWithRecap->id} and recap #{$recap->id} (denied_at: {$deniedAt})\n";

    Artisan::call('recaps:enforce-deadlines');

    $recap->refresh();
    $eventWithRecap->refresh();
    echo "After command – Recap #{$recap->id}: status = " . ($recap->status ?? 'null') . ", auto_deducted_at = " . ($recap->auto_deducted_at ?? 'null') . "\n";
    echo "Event #{$eventWithRecap->id}: recap_status = " . ($eventWithRecap->recap_status ?? 'null') . ", recap_payout_amount = " . ($eventWithRecap->recap_payout_amount ?? 'null') . "\n";

    $rejectedLogs = RecapDeductionLog::where('reason', 'rejected_not_resubmitted_within_24_hours')->where('recap_id', $recap->id)->get();
    echo "Deduction logs for rejected recap: " . $rejectedLogs->count() . "\n";
}

echo "\n=== Done. You can inspect Event, Recap, RecapDeductionLog in Tinker. ===\n";
