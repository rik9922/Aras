<?php

namespace Tests\Feature;

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

class AmbassadorEventListingTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function assigned_and_completed_tabs_partition_approved_assignments_by_event_status(): void
    {
        $role = Role::firstOrCreate(['name' => 'Ambassador']);
        $ambassador = User::create([
            'name' => 'Event Listing Ambassador',
            'email' => uniqid('ambassador-events-', true).'@test.local',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'status' => '1',
            'wage' => 20,
        ]);
        $managerRole = Role::firstOrCreate(['name' => 'Manager']);
        $manager = User::create([
            'name' => 'Event Listing Manager',
            'email' => uniqid('manager-events-', true).'@test.local',
            'password' => Hash::make('password'),
            'role_id' => $managerRole->id,
            'status' => '1',
        ]);
        $location = EventLocation::create(['name' => 'Listing Test Venue']);
        $department = EventDepartment::create(['name' => 'Listing Test Department']);
        $eventType = EventType::create(['name' => 'Listing Test Type']);
        $distributor = EventDistributor::create(['name' => 'Listing Test Distributor']);
        $brand = Brand::create(['brand_name' => 'Listing Test Brand']);
        $market = Market::create(['name' => 'Listing Test Market']);

        $activeEvents = collect();
        $completedEvents = collect();

        foreach (range(1, 5) as $index) {
            $completed = $index > 3;
            $event = Event::create([
                'event_name' => "Ambassador listing event {$index}",
                'event_location_id' => $location->id,
                'department_id' => $department->id,
                'event_type_id' => $eventType->id,
                'distributor_id' => $distributor->id,
                'brand_id' => $brand->id,
                'market_id' => $market->id,
                'manager_id' => $manager->id,
                'event_status' => $completed ? '1' : '0',
                'start_time' => now()->addDays($index),
                'end_time' => now()->addDays($index)->addHours(2),
                'booked_date' => now()->toDateString(),
                'booked_by' => 'tester',
                'timezone' => 'UTC',
                'position' => 1,
                // Deliberately exclude the ambassador from the legacy CSV field.
                'ambassador_id' => '999999',
            ]);

            AssignAmbassador::create([
                'event_id' => $event->id,
                'ambassador_id' => $ambassador->id,
                'status' => '1',
            ]);

            ($completed ? $completedEvents : $activeEvents)->push($event);
        }

        $this->actingAs($ambassador);

        $assignedResponse = $this->getJson(route('ambassador.events', ['status' => 'assigned']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $assignedResponse->assertOk()->assertJsonPath('count', 3);

        foreach ($activeEvents as $event) {
            $this->assertStringContainsString("show-event/{$event->id}", $assignedResponse->json('html'));
        }
        foreach ($completedEvents as $event) {
            $this->assertStringNotContainsString("show-event/{$event->id}", $assignedResponse->json('html'));
        }

        $completedResponse = $this->getJson(route('ambassador.events', ['status' => 'completed']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $completedResponse->assertOk()->assertJsonPath('count', 2);

        foreach ($completedEvents as $event) {
            $this->assertStringContainsString("show-event/{$event->id}", $completedResponse->json('html'));
        }
        foreach ($activeEvents as $event) {
            $this->assertStringNotContainsString("show-event/{$event->id}", $completedResponse->json('html'));
        }
    }
}
