# Testing Recap Deadline Enforcement

Step-by-step instructions to test automated recap deadline handling and payment adjustment.

## Prerequisites

- Migration run: `php artisan migrate` (includes `2026_03_16_000001_add_recap_deadline_fields`)
- At least one **manager** user (role_id 1 or 6) for notifications
- Optional: one **ambassador** user (role_id 5) for the “rejected recap” test

---

## Step 1: Test “Missing recap after 48 hours”

### 1.1 Create a test event (no recap) that ended 48+ hours ago

In **Tinker** (`php artisan tinker`):

```php
use App\Models\Event;
use Carbon\Carbon;

$endTime = Carbon::now()->subHours(50);
$event = Event::create([
    'event_name'   => 'Test – No recap ' . now()->format('Y-m-d H:i'),
    'recap_needed' => 1,
    'event_status' => '1',
    'start_time'   => $endTime->copy()->subHours(2),
    'end_time'     => $endTime,
    'booked_date'  => $endTime->toDateString(),
    'manager_id'   => \App\Models\User::whereIn('role_id', [1, 6])->value('id'),
    'timezone'     => 'UTC',
]);
echo "Created event id: {$event->id}\n";
```

### 1.2 Run the deadline enforcement command

```bash
php artisan recaps:enforce-deadlines
```

Or in Tinker:

```php
Artisan::call('recaps:enforce-deadlines');
```

### 1.3 Verify the result

In Tinker:

```php
$event->refresh();
// Expect: recap_status = "overdue", recap_payout_amount = 0
echo $event->recap_status;      // "overdue"
echo $event->recap_payout_amount; // 0.00

// Audit log entry
\App\Models\RecapDeductionLog::where('event_id', $event->id)->first();
// Expect: reason = "missing_recap_after_48_hours"
```

**Pass criteria:** Event has `recap_status = overdue`, `recap_payout_amount = 0`, and one `RecapDeductionLog` with reason `missing_recap_after_48_hours`. Manager receives email (if `manager_id` is set and mail is configured).

---

## Step 2: Test “Rejected recap not resubmitted within 24 hours”

### 2.1 Create an event and a rejected recap (denied 24+ hours ago)

In Tinker:

```php
use App\Models\Event;
use App\Models\Recap;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

$managerId = User::whereIn('role_id', [1, 6])->value('id');
$ambassador = User::where('role_id', 5)->first();
if (!$ambassador) { echo "Create an ambassador user (role_id 5) first.\n"; exit; }

$deniedAt = Carbon::now()->subHours(25);
$event = Event::create([
    'event_name'   => 'Test – Rejected recap ' . now()->format('Y-m-d H:i'),
    'recap_needed' => 1,
    'event_status' => '1',
    'start_time'   => $deniedAt->copy()->subHours(2),
    'end_time'     => $deniedAt->copy()->addHour(),
    'booked_date'  => $deniedAt->toDateString(),
    'manager_id'   => $managerId,
    'timezone'     => 'UTC',
]);

$recap = Recap::create([
    'event_id'     => $event->id,
    'ambassador_id' => $ambassador->id,
    'close_time'   => $deniedAt,
    'traffic_location' => 'Test',
    'check_in_with_person' => 'Test',
    'interaction_gender' => 'Test',
    'interaction_age' => 'Test',
    'total_interaction' => 'Test',
    'state_id'     => \App\Models\State::first()?->id,
    'is_approved'   => 0,
    'deny_reason'  => 'Test rejection',
    'denied_at'    => $deniedAt,
]);
DB::table('recaps')->where('id', $recap->id)->update(['updated_at' => $deniedAt]);

echo "Created event #{$event->id}, recap #{$recap->id}\n";
```

### 2.2 Run the command

```bash
php artisan recaps:enforce-deadlines
```

### 2.3 Verify the result

In Tinker:

```php
$recap->refresh();
$event->refresh();
// Recap: status = "auto_zeroed", auto_deducted_at set
// Event: recap_status = "auto_zeroed", recap_payout_amount = 0
echo $recap->status;            // "auto_zeroed"
echo $event->recap_status;      // "auto_zeroed"
echo $event->recap_payout_amount; // 0.00

\App\Models\RecapDeductionLog::where('recap_id', $recap->id)->first();
// Expect: reason = "rejected_not_resubmitted_within_24_hours"
```

**Pass criteria:** Recap has `status = auto_zeroed` and `auto_deducted_at` set; event has `recap_status = auto_zeroed` and `recap_payout_amount = 0`; one log with reason `rejected_not_resubmitted_within_24_hours`.

---

## Step 3: Run the full Tinker script (both scenarios)

From project root:

```bash
php artisan tinker
```

Then:

```php
include 'scripts/test_recap_deadlines_tinker.php';
```

This creates both test scenarios, runs `recaps:enforce-deadlines`, and prints a summary.

---

## Step 4: Run the automated test (PHPUnit)

From the project root:

```bash
php artisan test --filter=RecapDeadlineEnforcementTest
```

Or:

```bash
./vendor/bin/phpunit tests/Feature/RecapDeadlineEnforcementTest.php
```

This runs the Feature test that asserts the same behaviour as Steps 1 and 2. The test uses `DatabaseTransactions`, so it expects your database to be migrated and to have at least one user (ideally with `role_id` 1 or 6 for manager, and optionally `role_id` 5 for ambassador).

---

## Quick checklist

| Step | What to do | Pass when |
|------|------------|-----------|
| 1 | Event with no recap, end_time 48+ hours ago → run command | Event `recap_status=overdue`, `recap_payout_amount=0`, log exists |
| 2 | Rejected recap, denied 24+ hours ago, not updated → run command | Recap `status=auto_zeroed`, event `recap_payout_amount=0`, log exists |
| 3 | Run `include 'scripts/test_recap_deadlines_tinker.php'` in Tinker | Script prints success for both scenarios |
| 4 | Run `php artisan test --filter=RecapDeadlineEnforcementTest` | All tests pass |
