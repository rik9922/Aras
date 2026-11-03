<?php

namespace Tests\Feature;

use App\Mail\NotificationMail;
use App\Models\AssignAmbassador;
use App\Models\Event;
use App\Models\User;
use App\Services\LateCheckInNotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LateCheckInNotificationTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_notifies_manager_and_ba_when_check_in_is_late(): void
    {
        if (! Schema::hasTable('notifications')) {
            $this->markTestSkipped('notifications table missing');
        }

        Mail::fake();
        config(['attendance.late_checkin_grace_minutes' => 10]);

        $suffix = uniqid('', true);
        $manager = User::create([
            'name' => 'Late Mgr',
            'email' => "late_mgr_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 6,
            'status' => '1',
        ]);

        $ba = User::create([
            'name' => 'Late BA',
            'email' => "late_ba_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $event = Event::create([
            'event_name' => 'Late check-in event',
            'event_status' => '0',
            'start_time' => now()->subMinutes(15),
            'end_time' => now()->addHours(3),
            'booked_date' => now()->toDateString(),
            'event_address' => '500 Broadway, Nashville, TN, USA',
            'latitude' => 36.1627,
            'longitude' => -86.7816,
            'manager_id' => $manager->id,
            'timezone' => 'UTC',
        ]);

        $assignment = AssignAmbassador::withoutEvents(function () use ($event, $ba) {
            return AssignAmbassador::create([
                'event_id' => $event->id,
                'ambassador_id' => $ba->id,
                'status' => '1',
            ]);
        });

        app(LateCheckInNotificationService::class)->processDueAssignments();

        $assignment->refresh();
        $this->assertTrue((bool) $assignment->late_checkin_notification_sent);
        $this->assertNotNull($assignment->late_checkin_notification_sent_at);

        Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) use ($manager) {
            return $mail->hasTo($manager->email);
        });
        Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) use ($ba) {
            return $mail->hasTo($ba->email);
        });
    }

    #[Test]
    public function it_does_not_send_duplicate_late_check_in_alerts(): void
    {
        if (! Schema::hasTable('notifications')) {
            $this->markTestSkipped('notifications table missing');
        }

        Mail::fake();
        config(['attendance.late_checkin_grace_minutes' => 10]);

        $suffix = uniqid('', true);
        $ba = User::create([
            'name' => 'Late BA 2',
            'email' => "late_ba2_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $event = Event::create([
            'event_name' => 'Late duplicate event',
            'event_status' => '0',
            'start_time' => now()->subMinutes(20),
            'end_time' => now()->addHours(3),
            'booked_date' => now()->toDateString(),
            'event_address' => '600 Broadway, Nashville, TN, USA',
            'latitude' => 36.1627,
            'longitude' => -86.7816,
            'timezone' => 'UTC',
        ]);

        $assignment = AssignAmbassador::withoutEvents(function () use ($event, $ba) {
            return AssignAmbassador::create([
                'event_id' => $event->id,
                'ambassador_id' => $ba->id,
                'status' => '1',
                'late_checkin_notification_sent' => true,
                'late_checkin_notification_sent_at' => now(),
            ]);
        });

        $count = app(LateCheckInNotificationService::class)->processDueAssignments();

        $this->assertSame(0, $count);
        Mail::assertNothingSent();
    }
}
