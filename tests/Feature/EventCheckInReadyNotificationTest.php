<?php

namespace Tests\Feature;

use App\Models\AssignAmbassador;
use App\Models\Event;
use App\Models\User;
use App\Services\EventCheckInReadyNotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventCheckInReadyNotificationTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_notifies_ambassador_when_event_start_time_is_reached(): void
    {
        if (! Schema::hasTable('notifications')) {
            $this->markTestSkipped('notifications table missing');
        }

        if (! Schema::hasColumn('assign_ambassadors', 'check_in_ready_notification_sent')) {
            $this->markTestSkipped('check_in_ready_notification_sent column missing');
        }

        $suffix = uniqid('', true);
        $ba = User::create([
            'name' => 'Ready BA',
            'email' => "ready_ba_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $event = Event::create([
            'event_name' => 'Ready check-in event',
            'event_status' => '0',
            'start_time' => now()->subMinutes(2),
            'end_time' => now()->addHours(3),
            'booked_date' => now()->toDateString(),
            'event_address' => '500 Broadway, Nashville, TN, USA',
            'latitude' => 36.1627,
            'longitude' => -86.7816,
            'timezone' => 'UTC',
        ]);

        $assignment = AssignAmbassador::withoutEvents(function () use ($event, $ba) {
            return AssignAmbassador::create([
                'event_id' => $event->id,
                'ambassador_id' => $ba->id,
                'status' => '1',
            ]);
        });

        app(EventCheckInReadyNotificationService::class)->processDueAssignments();

        $assignment->refresh();
        $this->assertTrue((bool) $assignment->check_in_ready_notification_sent);
        $this->assertNotNull($assignment->check_in_ready_notification_sent_at);
    }

    #[Test]
    public function it_does_not_notify_before_event_start_time(): void
    {
        if (! Schema::hasColumn('assign_ambassadors', 'check_in_ready_notification_sent')) {
            $this->markTestSkipped('check_in_ready_notification_sent column missing');
        }

        $suffix = uniqid('', true);
        $ba = User::create([
            'name' => 'Future BA',
            'email' => "future_ba_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $event = Event::create([
            'event_name' => 'Future event',
            'event_status' => '0',
            'start_time' => now()->addHours(2),
            'end_time' => now()->addHours(5),
            'booked_date' => now()->toDateString(),
            'timezone' => 'UTC',
        ]);

        $assignment = AssignAmbassador::withoutEvents(function () use ($event, $ba) {
            return AssignAmbassador::create([
                'event_id' => $event->id,
                'ambassador_id' => $ba->id,
                'status' => '1',
            ]);
        });

        app(EventCheckInReadyNotificationService::class)->processDueAssignments();

        $assignment->refresh();
        $this->assertFalse((bool) $assignment->check_in_ready_notification_sent);
    }
}
