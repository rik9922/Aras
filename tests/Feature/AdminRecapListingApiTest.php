<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventDepartment;
use App\Models\EventDistributor;
use App\Models\EventLocation;
use App\Models\EventType;
use App\Models\Brand;
use App\Models\Market;
use App\Models\Recap;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminRecapListingApiTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function admin_recap_api_returns_all_matching_recaps_without_page_limit(): void
    {
        $suffix = uniqid('', true);
        $admin = User::create([
            'name' => 'Recap Admin',
            'email' => "recap_admin_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 1,
            'status' => '1',
        ]);

        $eventLocation = EventLocation::create([
            'name' => 'Recap Test Location',
            'city' => 'Milwaukee',
            'state' => 'WI',
        ]);
        $department = EventDepartment::create(['name' => 'Recap Test Department']);
        $eventType = EventType::create(['name' => 'Recap Test Type']);
        $distributor = EventDistributor::create(['name' => 'Recap Test Distributor']);
        $brand = Brand::create(['brand_name' => 'Recap Test Brand']);
        $market = Market::create(['name' => 'Recap Test Market']);

        for ($i = 0; $i < 30; $i++) {
            $event = Event::create([
                'event_location_id' => $eventLocation->id,
                'department_id' => $department->id,
                'event_type_id' => $eventType->id,
                'position' => 1,
                'distributor_id' => $distributor->id,
                'brand_id' => $brand->id,
                'market_id' => $market->id,
                'manager_id' => $admin->id,
                'start_time' => now()->subDays($i + 1),
                'end_time' => now()->subDays($i + 1)->addHours(2),
                'booked_date' => now()->subDays($i + 2),
                'booked_by' => 'Admin',
                'event_name' => 'Admin Recap Event '.$i,
                'event_status' => '1',
                'ambassador_id' => (string) $admin->id,
            ]);

            Recap::create([
                'event_id' => $event->id,
                'ambassador_id' => $admin->id,
                'created_by' => $admin->id,
            ]);
        }

        $token = JWTAuth::fromUser($admin);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/auth/v1/admin/get_recap?draw=1&ambassador_id='.$admin->id)
            ->assertOk();

        $this->assertStringContainsString('"status":"success"', $response->getContent());
        $this->assertSame(30, substr_count($response->getContent(), '<ion-icon name=\"eye\"'));
        $this->assertStringContainsString('"recordsFiltered":30', $response->getContent());
        $this->assertStringNotContainsString('"departments":', $response->getContent());
        $this->assertStringNotContainsString('"markets":', $response->getContent());
        $this->assertStringNotContainsString('"brands":', $response->getContent());
        $this->assertStringNotContainsString('"ambassadors":', $response->getContent());

        $searchResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/auth/v1/admin/get_recap?draw=2&start=0&length=10&search%5Bvalue%5D=Admin%20Recap%20Event%2029')
            ->assertOk();

        $this->assertSame(1, substr_count($searchResponse->getContent(), '<ion-icon name=\"eye\"'));
        $this->assertStringContainsString('"recordsFiltered":1', $searchResponse->getContent());
    }

    #[Test]
    public function recap_filter_options_are_loaded_by_typeahead_search(): void
    {
        $suffix = uniqid('', true);
        $admin = User::create([
            'name' => 'Recap Filter Admin',
            'email' => "recap_filter_admin_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 1,
            'status' => '1',
        ]);
        EventDepartment::create(['name' => 'Needle Department '.$suffix]);
        EventDepartment::create(['name' => 'Other Department '.$suffix]);

        $token = JWTAuth::fromUser($admin);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/v1/admin/search_recap_filters?type=departments&q=N')
            ->assertOk()
            ->assertJsonPath('data', []);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/v1/admin/search_recap_filters?type=departments&q=Needle')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonFragment(['name' => 'Needle Department '.$suffix])
            ->assertJsonMissing(['name' => 'Other Department '.$suffix]);
    }
}
