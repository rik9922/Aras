<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Services\ClientBudgetService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientBudgetServiceTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_requests_approval_when_allocation_max_would_be_exceeded_and_enforced(): void
    {
        if (! Schema::hasColumn('users', 'budget_max_events')) {
            $this->markTestSkipped('Budget migration not applied.');
        }

        $client = User::where('role_id', 3)->first();
        if (! $client) {
            $this->markTestSkipped('No client user (role_id 3) in database.');
        }

        $client->budget_max_events = 1;
        $client->budget_max_amount = null;
        $client->budget_enforce = true;
        $client->budget_require_approval = true;
        $client->save();

        $existing = Event::where('client_id', $client->id)->where('event_status', '1')->orderByDesc('id')->first();
        if (! $existing) {
            $existing = Event::create([
                'event_name' => 'Budget test — existing completed',
                'event_status' => '1',
                'client_id' => $client->id,
                'booked_date' => now()->toDateString(),
                'timezone' => 'UTC',
            ]);
        } else {
            $existing->update(['client_id' => $client->id, 'event_status' => '1']);
        }

        $pending = Event::create([
            'event_name' => 'Budget test — pending',
            'event_status' => '0',
            'client_id' => $client->id,
            'booked_date' => now()->toDateString(),
            'timezone' => 'UTC',
        ]);

        $service = app(ClientBudgetService::class);
        $result = $service->evaluateMarkCompleted($pending, $client->fresh());

        $this->assertFalse($result['allowed']);
        $this->assertTrue($result['pending']);
    }

    #[Test]
    public function billable_total_matches_sum_of_line_amounts(): void
    {
        $service = app(ClientBudgetService::class);
        $this->assertSame(120.5, $service->billableTotal(100.0, 20.0, 0.5));
    }

    #[Test]
    public function pending_events_are_included_in_allocation_usage(): void
    {
        if (! Schema::hasColumn('users', 'budget_max_events')) {
            $this->markTestSkipped('Budget migration not applied.');
        }

        $client = User::where('role_id', 3)->first();
        if (! $client) {
            $this->markTestSkipped('No client user (role_id 3) in database.');
        }

        $client->budget_max_events = 1;
        $client->budget_max_amount = null;
        $client->budget_enforce = true;
        $client->budget_require_approval = false;
        $client->save();

        Event::create([
            'event_name' => 'Budget test — existing pending',
            'event_status' => '0',
            'client_id' => $client->id,
            'booked_date' => now()->toDateString(),
            'timezone' => 'UTC',
        ]);

        $service = app(ClientBudgetService::class);
        $result = $service->evaluateTrackedEvent($client->fresh(), 0.0, 0.0, 0.0);

        $this->assertFalse($result['allowed']);
        $this->assertFalse($result['pending']);
        $this->assertStringContainsString('Cannot save event', (string) $result['message']);
    }
}
