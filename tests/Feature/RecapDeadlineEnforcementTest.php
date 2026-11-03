<?php

namespace Tests\Feature;

use App\Mail\RecapOverdueManagerNotification;
use App\Models\AssignAmbassador;
use App\Models\Event;
use App\Models\Notification;
use App\Models\Recap;
use App\Models\RecapDeductionLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecapDeadlineEnforcementTest extends TestCase
{
    use DatabaseTransactions;

    /** Require at least one manager user (role_id 1 or 6) in DB. */
    protected function getManager(): ?User
    {
        return User::whereIn('role_id', [1, 6])->first();
    }

    /** Any user as ambassador for rejected-recap test. */
    protected function getAmbassador(): User
    {
        $ambassador = User::where('role_id', 5)->first();
        return $ambassador ?? User::first();
    }

    #[Test]
    public function it_marks_events_with_missing_recap_after_48_hours_as_overdue_and_logs(): void
    {
        Mail::fake();
        $manager = $this->getManager();
        $endTime = Carbon::now()->subHours(50);

        $event = Event::create([
            'event_name'   => 'Test event – no recap',
            'recap_needed' => 1,
            'event_status' => '1',
            'start_time'   => $endTime->copy()->subHours(2),
            'end_time'     => $endTime,
            'booked_date'  => $endTime->toDateString(),
            'manager_id'   => $manager?->id,
            'timezone'     => 'UTC',
        ]);

        $logsBefore = RecapDeductionLog::where('event_id', $event->id)->count();

        Artisan::call('recaps:enforce-deadlines');

        $event->refresh();
        $this->assertSame('overdue', $event->recap_status);
        $this->assertEquals(0, (float) $event->recap_payout_amount);

        $log = RecapDeductionLog::where('event_id', $event->id)->first();
        $this->assertNotNull($log);
        $this->assertSame('missing_recap_after_48_hours', $log->reason);
    }

    #[Test]
    public function it_auto_zeroes_rejected_recap_not_resubmitted_within_24_hours_and_logs(): void
    {
        Mail::fake();
        $manager = $this->getManager();
        $ambassador = $this->getAmbassador();
        $this->assertNotNull($ambassador, 'Need at least one user in DB to run this test.');
        $deniedAt = Carbon::now()->subHours(25);

        $event = Event::create([
            'event_name'   => 'Test event – rejected recap',
            'recap_needed' => 1,
            'event_status' => '1',
            'start_time'   => $deniedAt->copy()->subHours(2),
            'end_time'     => $deniedAt->copy()->addHour(),
            'booked_date'  => $deniedAt->toDateString(),
            'manager_id'   => $manager?->id,
            'timezone'     => 'UTC',
        ]);

        $recap = Recap::create([
            'event_id'     => $event->id,
            'ambassador_id' => (string) $ambassador->id,
            'close_time'   => $deniedAt,
            'traffic_location' => 'Test',
            'check_in_with_person' => 'Test',
            'interaction_gender' => 'Test',
            'interaction_age' => 'Test',
            'total_interaction' => 'Test',
            'is_approved'  => 0,
            'deny_reason'  => 'Test',
            'denied_at'    => $deniedAt,
        ]);
        DB::table('recaps')->where('id', $recap->id)->update(['updated_at' => $deniedAt]);

        Artisan::call('recaps:enforce-deadlines');

        $recap->refresh();
        $event->refresh();

        $this->assertSame('auto_zeroed', $recap->status);
        $this->assertNotNull($recap->auto_deducted_at);
        $this->assertSame('auto_zeroed', $event->recap_status);
        $this->assertEquals(0, (float) $event->recap_payout_amount);

        $log = RecapDeductionLog::where('recap_id', $recap->id)->first();
        $this->assertNotNull($log);
        $this->assertSame('rejected_not_resubmitted_within_24_hours', $log->reason);
    }

    #[Test]
    public function it_sends_missing_recap_notifications_at_most_once_per_day_per_recipient(): void
    {
        if (! Schema::hasTable('recap_overdue_notification_marks')) {
            $this->markTestSkipped('recap_overdue_notification_marks table missing');
        }

        Mail::fake();
        $manager = $this->getManager();
        $ambassador = $this->getAmbassador();
        $this->assertNotNull($ambassador, 'Need at least one user in DB to run this test.');

        $endTime = Carbon::now()->subHours(50);
        $event = Event::create([
            'event_name' => 'Test event – daily recap dedupe',
            'recap_needed' => 1,
            'event_status' => '1',
            'start_time' => $endTime->copy()->subHours(2),
            'end_time' => $endTime,
            'booked_date' => $endTime->toDateString(),
            'manager_id' => $manager?->id,
            'timezone' => 'UTC',
        ]);

        AssignAmbassador::withoutEvents(function () use ($event, $ambassador) {
            AssignAmbassador::create([
                'event_id' => $event->id,
                'ambassador_id' => $ambassador->id,
                'status' => '1',
            ]);
        });

        Artisan::call('recaps:enforce-deadlines');
        Artisan::call('recaps:enforce-deadlines');

        $expectedMarkCount = $manager ? 2 : 1;

        $this->assertSame(
            $expectedMarkCount,
            DB::table('recap_overdue_notification_marks')
                ->where('event_id', $event->id)
                ->where('notified_date', now()->toDateString())
                ->count()
        );

        if ($manager?->email) {
            $this->assertSame(
                1,
                DB::table('recap_overdue_notification_marks')
                    ->where('event_id', $event->id)
                    ->where('recipient_id', $manager->id)
                    ->where('recipient_role', 'manager')
                    ->where('notified_date', now()->toDateString())
                    ->count()
            );

            Mail::assertSent(RecapOverdueManagerNotification::class, function (RecapOverdueManagerNotification $mail) use ($manager) {
                return $mail->hasTo($manager->email);
            });
        }

        $this->assertSame(
            1,
            Notification::query()
                ->where('receiver_id', $ambassador->id)
                ->where('type', 'missing_recap_overdue')
                ->where('booking_id', $event->id)
                ->count()
        );
    }
}
