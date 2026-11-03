<?php

namespace Tests\Feature;

use App\Models\Ambassador;
use App\Models\AssignAmbassador;
use App\Models\Brand;
use App\Models\Event;
use App\Models\EventDepartment;
use App\Models\EventDistributor;
use App\Models\EventLocation;
use App\Models\EventType;
use App\Models\Market;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AmbassadorEventListingApiTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function ambassador_event_lists_and_calendar_separate_pending_from_completed_events(): void
    {
        $suffix = uniqid('', true);
        $ambassadorRole = Role::firstOrCreate(['name' => 'Ambassador']);
        $managerRole = Role::firstOrCreate(['name' => 'Manager']);
        $ambassadorUser = User::create([
            'name' => 'Mobile Event Ambassador',
            'email' => "mobile-events-{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => $ambassadorRole->id,
            'status' => '1',
            'wage' => 20,
            'dob' => '1990-01-01',
        ]);
        $manager = User::create([
            'name' => 'Mobile Event Manager',
            'email' => "mobile-events-manager-{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => $managerRole->id,
            'status' => '1',
        ]);
        $ambassador = Ambassador::create([
            'user_id' => $ambassadorUser->id,
            'dob' => '1990-01-01',
            'wage' => 20,
        ]);
        $assignedMarket = Market::create(['name' => "Assigned Market {$suffix}"]);
        $otherMarket = Market::create(['name' => "Other Market {$suffix}"]);
        $ambassador->markets()->attach($assignedMarket->id);

        $location = EventLocation::create(['name' => "Mobile Venue {$suffix}"]);
        $department = EventDepartment::create(['name' => "Mobile Department {$suffix}"]);
        $eventType = EventType::create(['name' => "Mobile Type {$suffix}"]);
        $distributor = EventDistributor::create(['name' => "Mobile Distributor {$suffix}"]);
        $brand = Brand::create(['brand_name' => "Mobile Brand {$suffix}"]);

        $activeIds = [];
        $completedIds = [];
        $pendingCalendarDates = [];
        $calendarMonth = now()->addYear()->startOfMonth();
        foreach (range(1, 5) as $index) {
            $completed = $index > 3;
            $market = in_array($index, [3, 5], true) ? $otherMarket : $assignedMarket;
            $startTime = $calendarMonth->copy()->addDays($index);
            $event = Event::create([
                'event_name' => "Mobile assigned event {$index}",
                'event_location_id' => $location->id,
                'department_id' => $department->id,
                'event_type_id' => $eventType->id,
                'distributor_id' => $distributor->id,
                'brand_id' => $brand->id,
                'market_id' => $market->id,
                'manager_id' => $manager->id,
                'event_status' => $completed ? '1' : '0',
                'start_time' => $startTime,
                'end_time' => $startTime->copy()->addHours(2),
                'booked_date' => now()->toDateString(),
                'booked_by' => 'tester',
                'timezone' => 'UTC',
                'position' => 1,
                'ambassador_id' => '999999',
            ]);
            $event->markets()->attach($market->id);
            AssignAmbassador::create([
                'event_id' => $event->id,
                'ambassador_id' => $ambassadorUser->id,
                'status' => '1',
            ]);

            if ($completed) {
                $completedIds[] = $event->id;
            } else {
                $activeIds[] = $event->id;
                if ($market->is($assignedMarket)) {
                    $pendingCalendarDates[] = $startTime->toDateString();
                }
            }
        }

        $token = JWTAuth::fromUser($ambassadorUser);
        $assignedResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/v1/ambassador/get_events_data?draw=1&start=0&length=10&search%5Bvalue%5D=assigned&search%5Bregex%5D=true')
            ->assertOk()
            ->assertJsonPath('data.recordsTotal', 3);
        $this->assertEqualsCanonicalizing($activeIds, collect($assignedResponse->json('data.data'))->pluck('id')->all());

        $completedResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/v1/ambassador/get_events_data?draw=1&start=0&length=10&search%5Bvalue%5D=completed&search%5Bregex%5D=true')
            ->assertOk()
            ->assertJsonPath('data.recordsTotal', 2);
        $this->assertEqualsCanonicalizing($completedIds, collect($completedResponse->json('data.data'))->pluck('id')->all());

        $calendarResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/v1/ambassador/get_events?event_status=0&start_date='.$calendarMonth->toDateString())
            ->assertOk();
        $this->assertEqualsCanonicalizing($pendingCalendarDates, collect($calendarResponse->json('data'))->pluck('date')->all());
        $this->assertSame([1, 1], collect($calendarResponse->json('data'))->pluck('count')->map(fn ($count) => (int) $count)->all());

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/v1/ambassador/get_event_details?id='.$completedIds[0])
            ->assertOk()
            ->assertJsonPath('data.event.event_status', 1)
            ->assertJsonPath('data.event.is_assigned', 1);
    }
}
