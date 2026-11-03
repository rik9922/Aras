<?php

namespace Tests\Unit;

use App\Exports\SingleRecapDetailExport;
use App\Models\Event;
use App\Models\Recap;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SingleRecapDetailExportTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function export_includes_recap_response_fields(): void
    {
        $user = User::first();
        $this->assertNotNull($user);

        $event = Event::create([
            'event_name' => 'Detail export test',
            'event_status' => '1',
        ]);

        $recap = Recap::create([
            'event_id' => $event->id,
            'ambassador_id' => $user->id,
            'traffic_location' => 'Heavy foot traffic near registers',
            'consumer_feedback' => 'Loved the samples',
            'samples' => '42',
            'buyback_amount' => 15.50,
        ]);

        $export = new SingleRecapDetailExport($recap->fresh());
        $values = $export->collection()->pluck('Value', 'Field');

        $this->assertSame('Heavy foot traffic near registers', $values['Traffic at location (registers, aisles, etc.)']);
        $this->assertSame('Loved the samples', $values['Consumer feedback']);
        $this->assertSame('42', $values['Samples handed out']);
        $this->assertSame('15.50', $values['Buyback amount ($)']);
        $this->assertSame((string) $event->id, $values['Event ID']);
        $this->assertSame((string) $recap->id, $values['Recap ID']);
    }
}
