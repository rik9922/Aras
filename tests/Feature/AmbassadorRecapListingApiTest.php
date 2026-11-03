<?php

namespace Tests\Feature;

use App\Models\AssignAmbassador;
use App\Models\Ambassador;
use App\Models\Brand;
use App\Models\Event;
use App\Models\EventDepartment;
use App\Models\EventDistributor;
use App\Models\EventLocation;
use App\Models\EventType;
use App\Models\Market;
use App\Models\Recap;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AmbassadorRecapListingApiTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function ambassador_draft_api_is_scoped_and_supports_partial_payloads(): void
    {
        $suffix = uniqid('', true);
        $ambassador = User::create([
            'name' => 'Draft Ambassador',
            'email' => "draft_amb_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);
        $other = User::create([
            'name' => 'Other Ambassador',
            'email' => "other_amb_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);
        $event = $this->createValidEvent([
            'event_name' => 'Mobile Draft Event',
            'start_time' => Carbon::now()->subHours(2),
            'end_time' => Carbon::now()->subHour(),
            'recap_needed' => '1',
            'event_status' => '0',
        ]);
        AssignAmbassador::create(['event_id' => $event->id, 'ambassador_id' => $ambassador->id, 'status' => '1']);

        $token = JWTAuth::fromUser($ambassador);
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/v1/ambassador/save_recap_draft', [
                'event_id' => $event->id,
                'traffic_flow' => 'busy',
                'sampling_number' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('data.draft.payload.traffic_flow', 'busy')
            ->assertJsonPath('data.draft.payload.sampling_number', 3);

        $this->assertDatabaseHas('recap_drafts', [
            'ambassador_id' => $ambassador->id,
            'event_id' => $event->id,
        ]);

        $otherToken = JWTAuth::fromUser($other);
        $this->withHeader('Authorization', 'Bearer '.$otherToken)
            ->getJson('/api/auth/v1/ambassador/get_recap_draft?event_id='.$event->id)
            ->assertOk()
            ->assertJsonPath('data.draft', null);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/auth/v1/ambassador/delete_recap_draft', ['event_id' => $event->id])
            ->assertOk()
            ->assertJsonPath('data.deleted', true);
    }

    #[Test]
    public function create_and_submitted_recap_apis_use_jwt_user_and_match_listing_service(): void
    {
        $suffix = uniqid('', true);
        $ambassador = User::create([
            'name' => 'API Ambassador',
            'email' => "api_amb_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $openEvent = $this->createValidEvent([
            'event_name' => 'Open Recap Event',
            'start_time' => Carbon::now('America/New_York')->subHours(2),
            'end_time' => Carbon::now('America/New_York')->subHour(),
            'timezone' => 'America/New_York',
            'recap_needed' => '1',
            'event_status' => '0',
        ], $ambassador);

        $submittedEvent = $this->createValidEvent([
            'event_name' => 'Submitted Recap Event',
            'start_time' => Carbon::now('America/New_York')->subDay(),
            'end_time' => Carbon::now('America/New_York')->subDay()->addHours(2),
            'timezone' => 'America/New_York',
            'recap_needed' => '1',
            'event_status' => '1',
        ], $ambassador);

        foreach ([$openEvent, $submittedEvent] as $event) {
            AssignAmbassador::create([
                'event_id' => $event->id,
                'ambassador_id' => $ambassador->id,
                'status' => '1',
            ]);
        }

        Recap::create([
            'event_id' => $submittedEvent->id,
            'ambassador_id' => $ambassador->id,
            'is_draft' => false,
            'created_by' => $ambassador->id,
        ]);

        $token = JWTAuth::fromUser($ambassador);

        $createdResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/v1/ambassador/get_created_recap?draw=1&start=0&length=10&search[value]=')
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSame(1, $createdResponse->json('data.recordsTotal'));
        $this->assertSame($openEvent->id, $createdResponse->json('data.data.0.id'));
        $this->assertCount(1, $createdResponse->json('data.events'));

        $submittedResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/v1/ambassador/get_submitted_recap?draw=1&start=0&length=10&search[value]=')
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSame(1, $submittedResponse->json('data.recordsTotal'));
        $this->assertSame($submittedEvent->id, $submittedResponse->json('data.data.0.id'));
    }

    #[Test]
    public function recap_data_api_returns_compact_card_rows_for_current_ambassador(): void
    {
        $suffix = uniqid('', true);
        $ambassador = User::create([
            'name' => 'Card Ambassador',
            'email' => "card_amb_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);
        $other = User::create([
            'name' => 'Other Card Ambassador',
            'email' => "other_card_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);
        $manager = User::create([
            'name' => 'Perry Hudson',
            'email' => "manager_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 2,
            'status' => '1',
        ]);
        $brand = Brand::create(['brand_name' => 'Jack Daniel’s']);
        $location = EventLocation::create([
            'name' => 'Test Venue',
            'address' => '123 Main St',
            'city' => 'Mequon',
        ]);

        $event = $this->createValidEvent([
            'event_name' => 'Card Recap Event',
            'brand_id' => $brand->id,
            'event_location_id' => $location->id,
            'manager_id' => $manager->id,
            'start_time' => Carbon::parse('2026-09-08 18:00:00'),
            'end_time' => Carbon::parse('2026-09-08 20:00:00'),
            'ambassador_id' => (string) $ambassador->id,
            'recap_needed' => '1',
            'event_status' => '1',
        ]);
        $otherEvent = $this->createValidEvent([
            'event_name' => 'Other Recap Event',
            'brand_id' => $brand->id,
            'event_location_id' => $location->id,
            'manager_id' => $manager->id,
            'start_time' => Carbon::parse('2026-09-07 18:00:00'),
            'end_time' => Carbon::parse('2026-09-07 20:00:00'),
            'ambassador_id' => (string) $other->id,
            'recap_needed' => '1',
            'event_status' => '1',
        ]);

        $recap = Recap::create([
            'event_id' => $event->id,
            'ambassador_id' => $ambassador->id,
            'is_draft' => false,
            'is_approved' => true,
            'created_by' => $ambassador->id,
        ]);
        Recap::create([
            'event_id' => $event->id,
            'ambassador_id' => $ambassador->id,
            'is_draft' => true,
            'created_by' => $ambassador->id,
        ]);
        Recap::create([
            'event_id' => $otherEvent->id,
            'ambassador_id' => $other->id,
            'is_draft' => false,
            'created_by' => $other->id,
        ]);

        $token = JWTAuth::fromUser($ambassador);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/v1/ambassador/get_recap_data?draw=1&start=0&length=10')
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSame(1, $response->json('data.recordsTotal'));
        $this->assertSame($event->id, $response->json('data.data.0.id'));
        $this->assertSame($event->id, $response->json('data.data.0.event_id'));
        $this->assertSame($recap->id, $response->json('data.data.0.recap_id'));
        $this->assertSame('Card Recap Event', $response->json('data.data.0.event_name'));
        $this->assertSame('Perry Hudson', $response->json('data.data.0.manager'));
        $this->assertSame('Approved', $response->json('data.data.0.status'));
        $this->assertArrayNotHasKey('action', $response->json('data.data.0'));
    }

    private function createValidEvent(array $overrides = [], ?User $ambassador = null): Event
    {
        $suffix = uniqid('', true);
        $market = isset($overrides['market_id'])
            ? Market::find($overrides['market_id'])
            : Market::create(['name' => "Market {$suffix}", 'status' => Market::STATUS_ACTIVE]);

        if ($ambassador && $market) {
            $profile = Ambassador::firstOrCreate(
                ['user_id' => $ambassador->id],
                ['dob' => '1990-01-01']
            );
            $profile->markets()->syncWithoutDetaching([$market->id]);
        }

        $defaults = [
            'event_name' => "Event {$suffix}",
            'brand_id' => Brand::create(['brand_name' => "Brand {$suffix}"])->id,
            'event_location_id' => EventLocation::create([
                'name' => "Venue {$suffix}",
                'address' => '123 Main St',
                'city' => 'Mequon',
            ])->id,
            'department_id' => EventDepartment::create(['name' => "Department {$suffix}"])->id,
            'event_type_id' => EventType::create(['name' => "Type {$suffix}"])->id,
            'distributor_id' => EventDistributor::create(['name' => "Distributor {$suffix}"])->id,
            'market_id' => $market?->id,
            'manager_id' => User::create([
                'name' => "Manager {$suffix}",
                'email' => "manager_default_{$suffix}@test.local",
                'password' => Hash::make('password'),
                'role_id' => 2,
                'status' => '1',
            ])->id,
            'start_time' => Carbon::now()->subHours(2),
            'end_time' => Carbon::now()->subHour(),
            'position' => 1,
            'booked_date' => Carbon::now()->subDay(),
            'booked_by' => 'tester',
            'ambassador_id' => $ambassador ? (string) $ambassador->id : '',
            'recap_needed' => '1',
            'event_status' => '0',
        ];

        $event = Event::create(array_merge($defaults, $overrides));
        if ($event->market_id) {
            $event->markets()->syncWithoutDetaching([(int) $event->market_id]);
        }

        return $event;
    }
}
