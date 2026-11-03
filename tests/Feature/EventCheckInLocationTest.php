<?php

namespace Tests\Feature;

use App\Mail\NotificationMail;
use App\Models\AssignAmbassador;
use App\Models\Event;
use App\Models\User;
use App\Services\EventCheckInService;
use App\Services\GeoLocationService;
use App\Services\NotificationDispatchService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventCheckInLocationTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_rejects_check_in_outside_radius_without_force_flag(): void
    {
        config(['attendance.check_in_radius_meters' => 50]);

        $suffix = uniqid('', true);
        $ba = User::create([
            'name' => 'BA Check In',
            'email' => "ba_ci_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $event = Event::create([
            'event_name' => 'Location test event',
            'event_status' => '0',
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(4),
            'booked_date' => now()->toDateString(),
            'event_address' => '100 Main St',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'timezone' => 'UTC',
        ]);

        $assignment = AssignAmbassador::create([
            'event_id' => $event->id,
            'ambassador_id' => $ba->id,
            'status' => '1',
        ]);

        $service = app(EventCheckInService::class);
        $result = $service->processCheckIn(
            $ba,
            $event,
            $assignment,
            40.7200,
            -74.0060,
            false
        );

        $this->assertFalse($result['ok']);
        $this->assertSame('outside_event_radius', $result['code']);
        $this->assertTrue($result['requires_confirmation']);
    }

    #[Test]
    public function it_allows_forced_check_in_outside_radius_and_notifies_manager(): void
    {
        if (! Schema::hasTable('notifications')) {
            $this->markTestSkipped('notifications table missing');
        }

        config(['attendance.check_in_radius_meters' => 50]);

        $suffix = uniqid('', true);
        $manager = User::create([
            'name' => 'Mgr Check In',
            'email' => "mgr_ci_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 6,
            'status' => '1',
        ]);

        $ba = User::create([
            'name' => 'BA Outside',
            'email' => "ba_out_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $event = Event::create([
            'event_name' => 'Forced check-in event',
            'event_status' => '0',
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(4),
            'booked_date' => now()->toDateString(),
            'event_address' => '200 Main St',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'manager_id' => $manager->id,
            'timezone' => 'UTC',
        ]);

        $assignment = AssignAmbassador::create([
            'event_id' => $event->id,
            'ambassador_id' => $ba->id,
            'status' => '1',
        ]);

        $mock = $this->createMock(NotificationDispatchService::class);
        $mock->expects($this->once())
            ->method('dispatch')
            ->with(
                (int) $ba->id,
                (int) $manager->id,
                'event_check_in',
                $this->isType('string'),
                $this->stringContains('checked in, but is not at'),
                $this->isType('string'),
                (int) $event->id
            );
        $this->app->instance(NotificationDispatchService::class, $mock);

        $service = app(EventCheckInService::class);
        $result = $service->processCheckIn(
            $ba,
            $event,
            $assignment,
            40.7200,
            -74.0060,
            true
        );

        $this->assertTrue($result['ok']);
        $this->assertFalse($result['is_at_event_location']);
        $assignment->refresh();
        $this->assertFalse((bool) $assignment->is_at_event_location);
        $this->assertTrue((bool) $assignment->forced_check_in);
        $this->assertNotNull($assignment->distance_from_event_meters);
    }

    #[Test]
    public function it_emails_manager_on_check_in_even_when_email_preference_is_disabled(): void
    {
        if (! Schema::hasTable('notifications')) {
            $this->markTestSkipped('notifications table missing');
        }

        Mail::fake();
        config(['attendance.check_in_radius_meters' => 5000]);

        $suffix = uniqid('', true);
        $manager = User::create([
            'name' => 'Mgr Email',
            'email' => "mgr_mail_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 6,
            'status' => '1',
        ]);

        $ba = User::create([
            'name' => 'BA Email',
            'email' => "ba_mail_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $event = Event::create([
            'event_name' => 'Email notify event',
            'event_status' => '0',
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(4),
            'booked_date' => now()->toDateString(),
            'event_address' => '300 Main St',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'manager_id' => $manager->id,
            'timezone' => 'UTC',
        ]);

        $assignment = AssignAmbassador::create([
            'event_id' => $event->id,
            'ambassador_id' => $ba->id,
            'status' => '1',
        ]);

        if (Schema::hasTable('notification_preferences')) {
            \App\Models\NotificationPreference::updateOrCreate(
                ['user_id' => $manager->id],
                ['push_enabled' => true, 'email_enabled' => false]
            );
        }

        $service = app(EventCheckInService::class);
        $result = $service->processCheckIn(
            $ba,
            $event,
            $assignment,
            40.7128,
            -74.0060,
            false
        );

        $this->assertTrue($result['ok']);
        Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) use ($manager, $event, $ba) {
            if (! $mail->hasTo($manager->email)) {
                return false;
            }

            $html = $mail->render();

            return str_contains($html, 'Event details')
                && str_contains($html, (string) $event->event_name)
                && str_contains($html, '#' . $event->id)
                && str_contains($html, (string) $ba->name);
        });
    }

    #[Test]
    public function geo_location_service_calculates_distance_in_meters(): void
    {
        $geo = app(GeoLocationService::class);
        $distance = $geo->distanceInMeters(40.7128, -74.0060, 40.7128, -74.0050);
        $this->assertGreaterThan(50, $distance);
        $this->assertLessThan(120, $distance);
    }
}
