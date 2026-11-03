<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Event;
use App\Models\EventBillingParty;
use App\Models\EventDepartment;
use App\Models\EventDistributor;
use App\Models\EventLocation;
use App\Models\EventType;
use App\Models\Market;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompletedEventBillingEditTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function completed_event_edit_form_keeps_only_billing_parties_editable(): void
    {
        [$admin, $event] = $this->createCompletedEvent();

        $this->actingAs($admin)
            ->get(route('admin.edit.event', $event->id))
            ->assertOk()
            ->assertSee('The <strong>billing fields</strong> can be edited; event scheduling and details are locked.', false)
            ->assertSee('id="billingPartiesSection"', false)
            ->assertSee('fieldset class="row g-0 p-0 m-0 border-0" disabled', false);
    }

    #[Test]
    public function completed_event_update_changes_billing_only(): void
    {
        [$admin, $event] = $this->createCompletedEvent();
        $newClient = $this->createClient('New Billing Client');
        $originalStart = $event->start_time;
        $csrfToken = 'completed-event-billing-token';

        $this->actingAs($admin)
            ->withSession(['_token' => $csrfToken])
            ->post(route('admin.update.event'), [
                '_token' => $csrfToken,
                'id' => $event->id,
                'billing_party_ids' => [$newClient->id],
                'primary_billing_party_id' => $newClient->id,
                'labor_rates' => [$newClient->id => 30],
                'cups_amount' => 12,
                'buyback_amount' => 15,
                'payment_type' => 'Comdata',
                'expedite_fee' => 4, 'shipping_fee' => 5, 'parking_fee' => 6, 'drive_time' => '01:00:00',
                'notes' => 'This must not be changed',
                'start_time' => now()->addMonth(),
            ])
            ->assertRedirect(route('admin.events'));

        $event->refresh();
        $this->assertSame('Original completed notes', $event->notes);
        $this->assertSame($originalStart, $event->start_time);
        $this->assertSame($newClient->id, (int) $event->client_id);
        $this->assertSame(12.0, (float) $event->cups_amount);
        $this->assertSame(15.0, (float) $event->buyback_amount);
        $this->assertSame('Comdata', $event->payment_type);
        $this->assertSame(4.0, (float) $event->expedite_fee);
        $this->assertSame(5.0, (float) $event->shipping_fee);
        $this->assertSame(6.0, (float) $event->parking_fee);
        $this->assertSame('01:00:00', $event->drive_time);
        $this->assertSame(60.0, (float) $event->labor_amount);
        $this->assertDatabaseHas('event_billing_parties', [
            'event_id' => $event->id,
            'client_id' => $newClient->id,
            'is_primary' => 1,
            'labor_rate' => 30,
        ]);
    }

    private function createCompletedEvent(): array
    {
        $suffix = uniqid();
        $role = Role::firstOrCreate(['name' => 'Administrator']);
        $admin = User::create([
            'name' => 'Completed Billing Admin',
            'email' => 'completed-billing-admin-'.$suffix.'@test.local',
            'password' => 'password',
            'role_id' => $role->id,
            'status' => '1',
        ]);
        $client = $this->createClient('Original Billing Client');
        $location = EventLocation::create(['name' => 'Completed Billing Venue '.$suffix]);
        $department = EventDepartment::create(['name' => 'Completed Billing Department '.$suffix]);
        $eventType = EventType::create(['name' => 'Completed Billing Type '.$suffix]);
        $distributor = EventDistributor::create(['name' => 'Completed Billing Distributor '.$suffix]);
        $brand = Brand::create(['brand_name' => 'Completed Billing Brand '.$suffix]);
        $market = Market::create(['name' => 'Completed Billing Market '.$suffix]);

        $event = Event::create([
            'event_name' => 'Completed Billing Event',
            'event_location_id' => $location->id,
            'department_id' => $department->id,
            'event_type_id' => $eventType->id,
            'distributor_id' => $distributor->id,
            'brand_id' => $brand->id,
            'market_id' => $market->id,
            'manager_id' => $admin->id,
            'client_id' => $client->id,
            'event_status' => '1',
            'start_time' => '2026-11-01 10:00:00',
            'end_time' => '2026-11-01 12:00:00',
            'booked_date' => '2026-10-28',
            'booked_by' => $admin->email,
            'timezone' => 'UTC',
            'position' => 1,
            'ambassador_id' => '',
            'notes' => 'Original completed notes',
        ]);
        EventBillingParty::create([
            'event_id' => $event->id,
            'client_id' => $client->id,
            'is_primary' => 1,
            'sort_order' => 0,
            'labor_rate' => 20,
        ]);

        return [$admin, $event];
    }

    private function createClient(string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => uniqid('completed-billing-').'@test.local',
            'password' => 'password',
            'role_id' => 3,
            'status' => '1',
        ]);
    }
}
