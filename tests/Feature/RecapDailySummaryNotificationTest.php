<?php

namespace Tests\Feature;

use App\Mail\RecapDailySummaryManagerMail;
use App\Models\Event;
use App\Models\Recap;
use App\Models\RecapDailySummaryMark;
use App\Models\User;
use App\Services\RecapDailySummaryNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecapDailySummaryNotificationTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_sends_one_summary_email_to_managers_for_pending_recaps_in_window(): void
    {
        if (! Schema::hasTable('recap_daily_summary_marks')) {
            $this->markTestSkipped('Migration not applied: recap_daily_summary_marks');
        }

        Mail::fake();

        $asOf = Carbon::parse('2030-06-15 08:00:00');

        $suffix = uniqid('', true);
        $manager = User::create([
            'name' => 'Manager Daily Recap',
            'email' => "manager_daily_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 6,
            'status' => '1',
        ]);

        $ambassador = User::create([
            'name' => 'BA Daily Recap',
            'email' => "ba_daily_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $event = Event::create([
            'event_name' => 'Daily recap summary event',
            'booked_date' => '2030-06-14',
            'event_status' => '1',
            'recap_needed' => 1,
            'start_time' => Carbon::parse('2030-06-14 10:00:00'),
            'end_time' => Carbon::parse('2030-06-14 14:00:00'),
            'timezone' => 'UTC',
        ]);

        Recap::create([
            'event_id' => $event->id,
            'ambassador_id' => $ambassador->id,
            'created_by' => $ambassador->id,
            'is_draft' => false,
            'updated_at' => Carbon::parse('2030-06-15 07:30:00'),
            'created_at' => Carbon::parse('2030-06-15 07:30:00'),
        ]);

        $result = app(RecapDailySummaryNotificationService::class)->run(asOf: $asOf);

        $this->assertGreaterThanOrEqual(1, $result['recap_count']);
        $this->assertGreaterThanOrEqual(1, $result['managers_notified']);
        $this->assertFalse($result['skipped']);

        Mail::assertSent(RecapDailySummaryManagerMail::class, function (RecapDailySummaryManagerMail $mail) use ($manager) {
            return $mail->hasTo($manager->email);
        });
    }

    #[Test]
    public function it_does_not_send_when_no_pending_recaps_exist(): void
    {
        if (! Schema::hasTable('recap_daily_summary_marks')) {
            $this->markTestSkipped('Migration not applied: recap_daily_summary_marks');
        }

        Mail::fake();

        $result = app(RecapDailySummaryNotificationService::class)->run(
            asOf: Carbon::parse('1990-01-02 08:00:00')
        );

        $this->assertSame(0, $result['recap_count']);
        $this->assertTrue($result['skipped']);
        Mail::assertNothingSent();
    }

    #[Test]
    public function it_does_not_notify_the_same_recap_submission_twice(): void
    {
        if (! Schema::hasTable('recap_daily_summary_marks')) {
            $this->markTestSkipped('Migration not applied: recap_daily_summary_marks');
        }

        Mail::fake();

        $asOf = Carbon::parse('2030-06-15 08:00:00');

        $suffix = uniqid('', true);
        User::create([
            'name' => 'Manager Daily Recap',
            'email' => "manager_dup_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 6,
            'status' => '1',
        ]);

        $ambassador = User::create([
            'name' => 'BA Daily Recap',
            'email' => "ba_dup_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $event = Event::create([
            'event_name' => 'Duplicate summary event',
            'booked_date' => '2030-06-14',
            'event_status' => '1',
            'recap_needed' => 1,
            'start_time' => Carbon::parse('2030-06-14 10:00:00'),
            'end_time' => Carbon::parse('2030-06-14 14:00:00'),
            'timezone' => 'UTC',
        ]);

        $recap = Recap::create([
            'event_id' => $event->id,
            'ambassador_id' => $ambassador->id,
            'created_by' => $ambassador->id,
            'is_draft' => false,
            'updated_at' => Carbon::parse('2030-06-15 07:30:00'),
            'created_at' => Carbon::parse('2030-06-15 07:30:00'),
        ]);

        $service = app(RecapDailySummaryNotificationService::class);
        $service->run(asOf: $asOf);
        $sentAfterFirstRun = count(Mail::sent(RecapDailySummaryManagerMail::class));
        $this->assertGreaterThan(0, $sentAfterFirstRun);

        $secondRun = $service->run(asOf: $asOf);

        $this->assertSame(0, $secondRun['recap_count']);
        $this->assertTrue($secondRun['skipped']);
        $this->assertSame($sentAfterFirstRun, count(Mail::sent(RecapDailySummaryManagerMail::class)));

        $this->assertDatabaseHas('recap_daily_summary_marks', [
            'recap_id' => $recap->id,
        ]);
    }

    #[Test]
    public function it_includes_resubmitted_recaps_after_updated_at_changes(): void
    {
        if (! Schema::hasTable('recap_daily_summary_marks')) {
            $this->markTestSkipped('Migration not applied: recap_daily_summary_marks');
        }

        Mail::fake();

        $asOf = Carbon::parse('2031-01-20 08:00:00');

        $suffix = uniqid('', true);
        User::create([
            'name' => 'Manager Resubmit',
            'email' => "manager_resubmit_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 6,
            'status' => '1',
        ]);

        $ambassador = User::create([
            'name' => 'BA Resubmit',
            'email' => "ba_resubmit_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $event = Event::create([
            'event_name' => 'Resubmit summary event',
            'booked_date' => '2031-01-19',
            'event_status' => '1',
            'recap_needed' => 1,
            'start_time' => Carbon::parse('2031-01-19 10:00:00'),
            'end_time' => Carbon::parse('2031-01-19 14:00:00'),
            'timezone' => 'UTC',
        ]);

        $recap = Recap::create([
            'event_id' => $event->id,
            'ambassador_id' => $ambassador->id,
            'created_by' => $ambassador->id,
            'is_draft' => false,
            'updated_at' => Carbon::parse('2031-01-20 07:00:00'),
            'created_at' => Carbon::parse('2031-01-19 12:00:00'),
        ]);

        RecapDailySummaryMark::create([
            'recap_id' => $recap->id,
            'last_notified_updated_at' => Carbon::parse('2031-01-20 07:00:00'),
            'summary_date' => '2031-01-19',
        ]);

        $recap->update(['updated_at' => Carbon::parse('2031-01-20 07:45:00')]);

        $result = app(RecapDailySummaryNotificationService::class)->run(asOf: $asOf);

        $this->assertGreaterThanOrEqual(1, $result['recap_count']);
        Mail::assertSent(RecapDailySummaryManagerMail::class);

        $this->assertDatabaseHas('recap_daily_summary_marks', [
            'recap_id' => $recap->id,
            'last_notified_updated_at' => '2031-01-20 07:45:00',
        ]);
    }
}
