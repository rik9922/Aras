<?php

namespace Tests\Feature;

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

class PendingEventListingApiTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function admin_manager_and_client_event_tabs_return_only_pending_events(): void
    {
        $suffix = uniqid('', true);
        $admin = $this->createUser('Administrator', "pending-admin-{$suffix}@test.local");
        $manager = $this->createUser('Manager', "pending-manager-{$suffix}@test.local");
        $client = $this->createUser('Client', "pending-client-{$suffix}@test.local");
        $location = EventLocation::create(['name' => "Pending Venue {$suffix}"]);
        $department = EventDepartment::create(['name' => "Pending Department {$suffix}"]);
        $eventType = EventType::create(['name' => "Pending Type {$suffix}"]);
        $distributor = EventDistributor::create(['name' => "Pending Distributor {$suffix}"]);
        $brand = Brand::create(['brand_name' => "Pending Brand {$suffix}"]);
        $market = Market::create(['name' => "Pending Market {$suffix}"]);

        $events = collect(['0', '1', '2'])->mapWithKeys(function (string $status) use (
            $suffix,
            $manager,
            $client,
            $location,
            $department,
            $eventType,
            $distributor,
            $brand,
            $market,
        ) {
            $event = Event::create([
                'event_name' => "Role pending filter {$status} {$suffix}",
                'event_location_id' => $location->id,
                'department_id' => $department->id,
                'event_type_id' => $eventType->id,
                'distributor_id' => $distributor->id,
                'brand_id' => $brand->id,
                'market_id' => $market->id,
                'manager_id' => $manager->id,
                'client_id' => $client->id,
                'event_status' => $status,
                'start_time' => now()->addDays(7),
                'end_time' => now()->addDays(7)->addHours(2),
                'booked_date' => now()->toDateString(),
                'booked_by' => 'tester',
                'timezone' => 'UTC',
                'position' => 1,
                'ambassador_id' => '',
            ]);
            $event->markets()->attach($market->id);

            return [$status => $event];
        });

        foreach ([$admin, $manager] as $user) {
            $response = $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($user))
                ->getJson('/api/auth/v1/admin/get_event_list?status=0&start=0&length=20&draw=1&include_filters=0&brand_id='.$brand->id)
                ->assertOk();

            $this->assertSame([$events['0']->id], collect($response->json('data.data'))->pluck('id')->all());
        }

        $clientResponse = $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($client))
            ->getJson('/api/auth/v1/client/get_events_calendar_data?start=0&length=20&draw=1')
            ->assertOk()
            ->assertJsonPath('data.recordsTotal', 1);
        $this->assertSame([$events['0']->id], collect($clientResponse->json('data.data'))->pluck('id')->all());
    }

    private function createUser(string $roleName, string $email): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);

        return User::create([
            'name' => $roleName.' Pending Events',
            'email' => $email,
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'status' => '1',
        ]);
    }
}
