<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\EventController;
use App\Models\Event;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

class EventBestValidationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_apply_best_uses_request_id_to_load_existing_data(): void
    {
        $event = Event::create([
            'event_name' => 'BEST test event',
            'event_status' => '0',
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(3),
            'booked_date' => now()->toDateString(),
            'timezone' => 'UTC',
            'integrate_with_best' => 1,
            'best_event_data' => json_encode(['status' => 'Draft']),
        ]);

        $controller = app(EventController::class);
        $method = new ReflectionMethod(EventController::class, 'applyBestEventDataToValidated');
        $method->setAccessible(true);

        $request = Request::create('/admin/update-event', 'POST', [
            'id' => $event->id,
        ]);

        $validated = ['integrate_with_best' => true, 'best_event_data' => null];
        $result = $method->invoke($controller, $request, $validated);

        $this->assertIsArray($result);
        $this->assertSame($event->best_event_data, $result['best_event_data']);
    }

    public function test_apply_best_redirects_when_no_configuration_available(): void
    {
        $controller = app(EventController::class);
        $method = new ReflectionMethod(EventController::class, 'applyBestEventDataToValidated');
        $method->setAccessible(true);

        $request = Request::create('/admin/update-event', 'POST', [
            'id' => 999999999,
        ]);

        $validated = ['integrate_with_best' => true, 'best_event_data' => null];
        $result = $method->invoke($controller, $request, $validated);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $result);
    }
}
