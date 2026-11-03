<?php

namespace Tests\Feature;

use App\Models\AssignAmbassador;
use App\Models\AmbassadorFirstShiftManagerAlert;
use App\Models\Event;
use App\Models\User;
use App\Services\Ambassador\FirstShiftManagerAlertService;
use App\Services\NotificationDispatchService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FirstShiftManagerAlertTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_notifies_event_market_manager_on_trigger_day_only_once_per_ambassador(): void
    {
        if (! Schema::hasTable('em_ba_first_shift_alerts')) {
            $this->markTestSkipped('Migration not applied: em_ba_first_shift_alerts');
        }

        $suffix = uniqid('', true);
        $manager = User::create([
            'name' => 'EMM Test',
            'email' => "emm_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 6,
            'status' => '1',
        ]);

        $ba = User::create([
            'name' => 'BA First Shift',
            'email' => "ba_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $start = Carbon::parse('2026-04-25 10:00:00', 'UTC');
        Carbon::setTestNow(Carbon::parse('2026-04-23 12:00:00', 'UTC'));

        $event = Event::create([
            'event_name' => 'EMM first-shift test',
            'event_status' => '0',
            'start_time' => $start,
            'end_time' => $start->copy()->addHours(4),
            'booked_date' => '2026-04-20',
            'manager_id' => $manager->id,
            'timezone' => 'UTC',
        ]);

        AssignAmbassador::create([
            'event_id' => $event->id,
            'ambassador_id' => $ba->id,
            'status' => '1',
        ]);

        $mock = $this->createMock(NotificationDispatchService::class);
        $mock->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->identicalTo(null),
                (int) $manager->id,
                'ba_first_shift_2d',
                $this->isType('string'),
                $this->isType('string'),
                $this->identicalTo(null),
                $this->identicalTo(null)
            );
        $this->app->instance(NotificationDispatchService::class, $mock);

        /** @var FirstShiftManagerAlertService $service */
        $service = app(FirstShiftManagerAlertService::class);
        $r1 = $service->run();
        $this->assertSame(1, (int) ($r1['notified'] ?? 0), 'Expected one manager notification on trigger day');
        $this->assertNotNull(AmbassadorFirstShiftManagerAlert::where('ambassador_id', $ba->id)->value('notified_at'));

        $r2 = $service->run();
        $this->assertSame(0, (int) ($r2['notified'] ?? 0), 'No duplicate after record exists');

        Carbon::setTestNow();
    }

    #[Test]
    public function it_does_not_notify_before_the_two_day_trigger(): void
    {
        if (! Schema::hasTable('em_ba_first_shift_alerts')) {
            $this->markTestSkipped('Migration not applied: em_ba_first_shift_alerts');
        }

        $suffix = uniqid('', true);
        $manager = User::create([
            'name' => 'EMM Test 2',
            'email' => "emm2_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 6,
            'status' => '1',
        ]);

        $ba = User::create([
            'name' => 'BA First Shift 2',
            'email' => "ba2_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $start = Carbon::parse('2026-04-25 10:00:00', 'UTC');
        Carbon::setTestNow(Carbon::parse('2026-04-22 12:00:00', 'UTC'));

        $event = Event::create([
            'event_name' => 'EMM early test',
            'event_status' => '0',
            'start_time' => $start,
            'end_time' => $start->copy()->addHours(4),
            'booked_date' => '2026-04-20',
            'manager_id' => $manager->id,
            'timezone' => 'UTC',
        ]);

        AssignAmbassador::create([
            'event_id' => $event->id,
            'ambassador_id' => $ba->id,
            'status' => '1',
        ]);

        $mock = $this->createMock(NotificationDispatchService::class);
        $mock->expects($this->never())->method('dispatch');
        $this->app->instance(NotificationDispatchService::class, $mock);

        /** @var FirstShiftManagerAlertService $service */
        $service = app(FirstShiftManagerAlertService::class);
        $r = $service->run();
        $this->assertSame(0, (int) ($r['notified'] ?? 0));
        $this->assertFalse(AmbassadorFirstShiftManagerAlert::where('ambassador_id', $ba->id)->exists());

        Carbon::setTestNow();
    }
}
