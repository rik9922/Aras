<?php

namespace Tests\Feature;

use App\Mail\AssignAmbassadorMail;
use App\Models\AssignAmbassador;
use App\Models\Event;
use App\Models\Notification;
use App\Models\User;
use App\Services\EventAssignmentNotificationService;
use App\Services\NotificationDispatchService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventAssignmentNotificationTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function new_approved_assignment_creates_db_notification_push_and_email(): void
    {
        if (! Schema::hasTable('notifications')) {
            $this->markTestSkipped('notifications table missing');
        }

        Mail::fake();

        $suffix = uniqid('', true);
        $ambassador = User::create([
            'name' => 'Assigned BA',
            'email' => "assigned_ba_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $event = Event::create([
            'event_name' => 'Assignment Event',
            'event_status' => '0',
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(4),
            'booked_date' => now()->toDateString(),
            'event_address' => '100 Main St',
            'timezone' => 'UTC',
        ]);

        $mock = $this->createMock(NotificationDispatchService::class);
        $mock->expects($this->once())
            ->method('dispatch')
            ->with(
                null,
                (int) $ambassador->id,
                EventAssignmentNotificationService::NOTIFICATION_TYPE,
                'Assigned Ambassador',
                $this->stringContains('Assignment Event'),
                null,
                (int) $event->id
            )
            ->willReturn(new Notification([
                'sender_id' => null,
                'receiver_id' => $ambassador->id,
                'title' => 'Assigned Ambassador',
                'message' => 'You have been approved as an ambassador for Assignment Event.',
                'type' => EventAssignmentNotificationService::NOTIFICATION_TYPE,
                'booking_id' => $event->id,
                'is_seen' => 0,
            ]));
        $this->app->instance(NotificationDispatchService::class, $mock);

        AssignAmbassador::create([
            'event_id' => $event->id,
            'ambassador_id' => $ambassador->id,
            'status' => '1',
        ]);

        // One email — to the ambassador only. (The hardcoded dev copy to
        // user1@jtplapps.in was removed.)
        Mail::assertSent(AssignAmbassadorMail::class, 1);
        Mail::assertSent(AssignAmbassadorMail::class, function (AssignAmbassadorMail $mail) use ($ambassador) {
            return $mail->hasTo($ambassador->email);
        });
    }

    #[Test]
    public function pending_assignment_does_not_notify(): void
    {
        Mail::fake();

        $suffix = uniqid('', true);
        $ambassador = User::create([
            'name' => 'Pending BA',
            'email' => "pending_ba_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $event = Event::create([
            'event_name' => 'Pending Assignment Event',
            'event_status' => '0',
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(4),
            'booked_date' => now()->toDateString(),
            'event_address' => '200 Main St',
            'timezone' => 'UTC',
        ]);

        $mock = $this->createMock(NotificationDispatchService::class);
        $mock->expects($this->never())->method('dispatch');
        $this->app->instance(NotificationDispatchService::class, $mock);

        AssignAmbassador::create([
            'event_id' => $event->id,
            'ambassador_id' => $ambassador->id,
            'status' => '0',
        ]);

        Mail::assertNothingSent();
    }

    #[Test]
    public function re_approving_already_approved_assignment_does_not_duplicate_notification(): void
    {
        Mail::fake();

        $suffix = uniqid('', true);
        $ambassador = User::create([
            'name' => 'Reapproved BA',
            'email' => "reapproved_ba_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $event = Event::create([
            'event_name' => 'Reapprove Event',
            'event_status' => '0',
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(4),
            'booked_date' => now()->toDateString(),
            'event_address' => '300 Main St',
            'timezone' => 'UTC',
        ]);

        $assignment = AssignAmbassador::withoutEvents(function () use ($event, $ambassador) {
            return AssignAmbassador::create([
                'event_id' => $event->id,
                'ambassador_id' => $ambassador->id,
                'status' => '1',
            ]);
        });

        $mock = $this->createMock(NotificationDispatchService::class);
        $mock->expects($this->never())->method('dispatch');
        $this->app->instance(NotificationDispatchService::class, $mock);

        $assignment->status = '1';
        $assignment->save();

        Mail::assertNothingSent();
    }

    #[Test]
    public function assignment_edit_without_status_change_does_not_duplicate_notification(): void
    {
        Mail::fake();

        $suffix = uniqid('', true);
        $ambassador = User::create([
            'name' => 'Edited BA',
            'email' => "edited_ba_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $event = Event::create([
            'event_name' => 'Edit Event',
            'event_status' => '0',
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(4),
            'booked_date' => now()->toDateString(),
            'event_address' => '400 Main St',
            'timezone' => 'UTC',
        ]);

        $assignment = AssignAmbassador::withoutEvents(function () use ($event, $ambassador) {
            return AssignAmbassador::create([
                'event_id' => $event->id,
                'ambassador_id' => $ambassador->id,
                'status' => '1',
                'override_pay' => 0,
            ]);
        });

        $mock = $this->createMock(NotificationDispatchService::class);
        $mock->expects($this->never())->method('dispatch');
        $this->app->instance(NotificationDispatchService::class, $mock);

        $assignment->override_pay = 25;
        $assignment->save();

        Mail::assertNothingSent();
    }

    #[Test]
    public function status_transition_from_pending_to_approved_notifies_once(): void
    {
        Mail::fake();

        $suffix = uniqid('', true);
        $ambassador = User::create([
            'name' => 'Transition BA',
            'email' => "transition_ba_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $event = Event::create([
            'event_name' => 'Transition Event',
            'event_status' => '0',
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(4),
            'booked_date' => now()->toDateString(),
            'event_address' => '500 Main St',
            'timezone' => 'UTC',
        ]);

        $assignment = AssignAmbassador::withoutEvents(function () use ($event, $ambassador) {
            return AssignAmbassador::create([
                'event_id' => $event->id,
                'ambassador_id' => $ambassador->id,
                'status' => '0',
            ]);
        });

        $mock = $this->createMock(NotificationDispatchService::class);
        $mock->expects($this->once())->method('dispatch');
        $this->app->instance(NotificationDispatchService::class, $mock);

        $assignment->status = '1';
        $assignment->save();

        // One email — to the ambassador only (dev copy recipient removed).
        Mail::assertSent(AssignAmbassadorMail::class, 1);
    }
}
