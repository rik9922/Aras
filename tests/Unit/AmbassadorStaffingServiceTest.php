<?php

namespace Tests\Unit;

use App\Models\AssignAmbassador;
use App\Models\Event;
use App\Models\User;
use App\Services\AmbassadorStaffingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AmbassadorStaffingServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sync_staffing_applies_no_show_and_pinata_per_row(): void
    {
        $event = Event::create([
            'event_name' => 'Staffing sync test',
            'pinata_pay' => 0,
            'ambassador_id' => '',
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(4),
            'booked_date' => now()->toDateString(),
            'timezone' => 'UTC',
        ]);

        $ambassador = User::create([
            'name' => 'Test BA',
            'email' => 'ba_staffing_'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role_id' => 5,
            'wage' => 50,
        ]);

        $assignment = AssignAmbassador::create([
            'event_id' => $event->id,
            'ambassador_id' => $ambassador->id,
            'status' => '1',
            'override_pay' => 0,
            'pinata_amount' => 0,
            'is_no_show' => false,
        ]);

        $service = app(AmbassadorStaffingService::class);
        $service->syncStaffingFromForm($event, [
            [
                'assign_id' => $assignment->id,
                'ambassador_id' => $ambassador->id,
                'status' => '1',
                'is_no_show' => true,
                'has_pinata' => true,
                'override_pay' => 10,
            ],
        ], false, []);

        $assignment->refresh();
        $this->assertTrue($assignment->is_no_show);
        $this->assertSame(5, (int) $assignment->pinata_amount);
        $this->assertSame(10, (int) $assignment->override_pay);
    }
}
