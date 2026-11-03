<?php

namespace Tests\Unit;

use App\Models\AssignAmbassador;
use App\Models\Event;
use App\Models\Recap;
use App\Models\User;
use App\Services\AmbassadorRecapListingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AmbassadorRecapListingServiceTest extends TestCase
{
    use DatabaseTransactions;

    private AmbassadorRecapListingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AmbassadorRecapListingService::class);
    }

    #[Test]
    public function open_recap_events_match_web_filters_and_timezone_rules(): void
    {
        $suffix = uniqid('', true);
        $ambassador = User::create([
            'name' => 'Recap Ambassador',
            'email' => "recap_amb_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $readyEvent = Event::create([
            'event_name' => 'Ready Event',
            'start_time' => Carbon::now('America/New_York')->subHour(),
            'end_time' => Carbon::now('America/New_York')->addHour(),
            'timezone' => 'America/New_York',
            'recap_needed' => '1',
            'event_status' => '0',
        ]);

        $futureEvent = Event::create([
            'event_name' => 'Future Event',
            'start_time' => Carbon::now('America/New_York')->addDay(),
            'end_time' => Carbon::now('America/New_York')->addDay()->addHours(2),
            'timezone' => 'America/New_York',
            'recap_needed' => '1',
            'event_status' => '0',
        ]);

        $submittedEvent = Event::create([
            'event_name' => 'Submitted Event',
            'start_time' => Carbon::now('America/New_York')->subDay(),
            'end_time' => Carbon::now('America/New_York')->subDay()->addHours(2),
            'timezone' => 'America/New_York',
            'recap_needed' => '1',
            'event_status' => '0',
        ]);

        foreach ([$readyEvent, $futureEvent, $submittedEvent] as $event) {
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

        Recap::create([
            'event_id' => $readyEvent->id,
            'ambassador_id' => $ambassador->id,
            'is_draft' => true,
            'created_by' => $ambassador->id,
        ]);

        $openEventIds = $this->service->openRecapEventIdsForAmbassador($ambassador->id);
        $this->assertEqualsCanonicalizing(
            [$readyEvent->id, $futureEvent->id],
            $openEventIds
        );

        $openEvents = $this->service->openRecapEvents($ambassador->id);
        $this->assertSame([$readyEvent->id], $openEvents->pluck('id')->all());
    }

    #[Test]
    public function submitted_recap_events_exclude_drafts(): void
    {
        $suffix = uniqid('', true);
        $ambassador = User::create([
            'name' => 'Submitted Ambassador',
            'email' => "submitted_amb_{$suffix}@test.local",
            'password' => Hash::make('password'),
            'role_id' => 5,
            'status' => '1',
        ]);

        $submittedEvent = Event::create([
            'event_name' => 'Submitted Event',
            'start_time' => Carbon::now()->subDay(),
            'end_time' => Carbon::now()->subDay()->addHours(2),
            'recap_needed' => '1',
            'event_status' => '1',
        ]);

        $draftOnlyEvent = Event::create([
            'event_name' => 'Draft Only Event',
            'start_time' => Carbon::now()->subDay(),
            'end_time' => Carbon::now()->subDay()->addHours(2),
            'recap_needed' => '1',
            'event_status' => '1',
        ]);

        $newerSubmittedEvent = Event::create([
            'event_name' => 'Newer Submitted Event',
            'start_time' => Carbon::now()->subHours(2),
            'end_time' => Carbon::now()->subHour(),
            'recap_needed' => '1',
            'event_status' => '1',
        ]);

        Recap::create([
            'event_id' => $submittedEvent->id,
            'ambassador_id' => $ambassador->id,
            'is_draft' => false,
            'created_by' => $ambassador->id,
        ]);

        Recap::create([
            'event_id' => $newerSubmittedEvent->id,
            'ambassador_id' => $ambassador->id,
            'is_draft' => false,
            'created_by' => $ambassador->id,
        ]);

        Recap::create([
            'event_id' => $draftOnlyEvent->id,
            'ambassador_id' => $ambassador->id,
            'is_draft' => true,
            'created_by' => $ambassador->id,
        ]);

        $submittedIds = $this->service
            ->submittedRecapEventsQuery($ambassador->id)
            ->pluck('id')
            ->all();

        $this->assertSame([$newerSubmittedEvent->id, $submittedEvent->id], $submittedIds);
    }
}
