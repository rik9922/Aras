<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Services\EventTimezoneService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EventTimezoneServiceTest extends TestCase
{
    public function test_normalize_legacy_abbreviations_to_iana(): void
    {
        $service = app(EventTimezoneService::class);

        $this->assertSame('America/New_York', $service->normalizeIana('EST'));
        $this->assertSame('America/Chicago', $service->normalizeIana('CST'));
        $this->assertSame('America/Los_Angeles', $service->normalizeIana('America/Los_Angeles'));
    }

    public function test_display_label_includes_region_name(): void
    {
        $service = app(EventTimezoneService::class);

        $label = $service->displayLabel('America/New_York', 'EST', '2026-01-15 17:00:00');

        $this->assertStringContainsString('EST', $label);
        $this->assertStringContainsString('Eastern', $label);
    }

    public function test_apply_to_event_data_from_coordinates(): void
    {
        Http::fake([
            'maps.googleapis.com/maps/api/timezone/json*' => Http::response([
                'status' => 'OK',
                'timeZoneId' => 'America/Chicago',
            ], 200),
        ]);

        $service = app(EventTimezoneService::class);
        $data = $service->applyToEventData([
            'latitude' => 41.8781,
            'longitude' => -87.6298,
            'start_time' => '2026-07-15 17:00:00',
        ]);

        $this->assertSame('America/Chicago', $data['timezone']);
        $this->assertNotEmpty($data['timezone_label']);
    }

    public function test_format_event_time_range_uses_event_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-25 12:00:00', 'America/Los_Angeles'));

        $event = new Event([
            'start_time' => '2026-05-25 17:00:00',
            'end_time' => '2026-05-25 20:00:00',
            'timezone' => 'America/New_York',
            'timezone_label' => 'EST',
        ]);

        $formatted = app(EventTimezoneService::class)->formatEventTimeRange($event);

        $this->assertStringContainsString('5:00 PM', $formatted);
        $this->assertStringContainsString('8:00 PM', $formatted);
        $this->assertStringContainsString('EST', $formatted);

        Carbon::setTestNow();
    }
}
