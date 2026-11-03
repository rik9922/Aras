<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Recap;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceMetricsAndStatusesTest extends TestCase
{
    use DatabaseTransactions;

    private function makeAdmin(): User
    {
        return User::create([
            'name' => 'Test Admin',
            'email' => 'admin_test_'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
        ]);
    }

    private function makeClient(string $qbCustomerId): User
    {
        return User::create([
            'name' => 'Test Client',
            'email' => 'client_test_'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role_id' => 2,
            'quickbooks_customer_id' => $qbCustomerId,
        ]);
    }

    #[Test]
    public function qb_transfer_is_blocked_for_no_show(): void
    {
        $admin = $this->makeAdmin();
        Cache::put('qb_access_token', 'test');
        Cache::put('qb_realm_id', 'test');

        $event = Event::create([
            'event_name' => 'QB block test',
            'admin_status' => 'no_show',
            'start_time' => now()->subHour(),
            'end_time' => now(),
            'booked_date' => now()->toDateString(),
            'timezone' => 'UTC',
        ]);

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->from('/admin/events')
            ->post(route('admin.event.transfer-to-quickbooks', $event), ['_token' => 'test-token'])
            ->assertRedirect('/admin/events');

        $event->refresh();
        $this->assertSame('failed', $event->qb_publish_status);
        $this->assertNotNull($event->qb_publish_error);
    }

    #[Test]
    public function invoice_metrics_counts_no_show_late_cancelled(): void
    {
        $admin = $this->makeAdmin();
        Cache::put('qb_access_token', 'test');
        Cache::put('qb_realm_id', 'test');

        $qbCustomerId = 'QB-CUST-123';
        $client = $this->makeClient($qbCustomerId);

        $month = Carbon::parse('2026-03-01')->startOfMonth();

        // Submitted on time
        $e1 = Event::create([
            'client_id' => $client->id,
            'recap_needed' => 1,
            'start_time' => $month->copy()->addDays(1)->setTime(10, 0),
            'end_time' => $month->copy()->addDays(1)->setTime(12, 0),
            'booked_date' => $month->copy()->addDays(1)->toDateString(),
            'timezone' => 'UTC',
        ]);
        Recap::create([
            'event_id' => $e1->id,
            'ambassador_id' => '1',
            'close_time' => $e1->end_time,
            'traffic_location' => 'Test',
            'check_in_with_person' => 'Test',
            'interaction_gender' => 'Test',
            'interaction_age' => 'Test',
            'total_interaction' => 'Test',
        ]);

        // Late submitted ( > 24h after end_time )
        $e2 = Event::create([
            'client_id' => $client->id,
            'recap_needed' => 1,
            'start_time' => $month->copy()->addDays(2)->setTime(10, 0),
            'end_time' => $month->copy()->addDays(2)->setTime(12, 0),
            'booked_date' => $month->copy()->addDays(2)->toDateString(),
            'timezone' => 'UTC',
        ]);
        $r2 = Recap::create([
            'event_id' => $e2->id,
            'ambassador_id' => '1',
            'close_time' => $e2->end_time,
            'traffic_location' => 'Test',
            'check_in_with_person' => 'Test',
            'interaction_gender' => 'Test',
            'interaction_age' => 'Test',
            'total_interaction' => 'Test',
        ]);
        $r2->created_at = Carbon::parse($e2->end_time)->addHours(25);
        $r2->save();

        // No show
        Event::create([
            'client_id' => $client->id,
            'admin_status' => 'no_show',
            'recap_needed' => 1,
            'start_time' => $month->copy()->addDays(3)->setTime(10, 0),
            'end_time' => $month->copy()->addDays(3)->setTime(12, 0),
            'booked_date' => $month->copy()->addDays(3)->toDateString(),
            'timezone' => 'UTC',
        ]);

        // Cancelled
        Event::create([
            'client_id' => $client->id,
            'admin_status' => 'cancelled_no_fee',
            'recap_needed' => 0,
            'start_time' => $month->copy()->addDays(4)->setTime(10, 0),
            'end_time' => $month->copy()->addDays(4)->setTime(12, 0),
            'booked_date' => $month->copy()->addDays(4)->toDateString(),
            'timezone' => 'UTC',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.event-invoicing', ['month' => '2026-03']));
        $response->assertOk();

        $response->assertSee($client->name);
        $response->assertSee('No shows');
        $response->assertSee('Late');
        $response->assertSee('Cancelled');
        $response->assertSee('2'); // submitted count in table
        $response->assertSee('1'); // other metric columns
    }
}

