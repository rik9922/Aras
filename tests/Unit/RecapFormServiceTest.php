<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Recap;
use App\Services\BEST\EventPushService;
use App\Services\RecapFormService;
use App\Services\BEST\TokenService;
use Tests\TestCase;

class RecapFormServiceTest extends TestCase
{
    public function test_best_event_takes_priority_over_event_type_recap_type(): void
    {
        $eventType = new EventType(['recap_type' => RecapFormService::TYPE_ON_PREMISE]);
        $event = new Event(['integrate_with_best' => 1]);
        $event->setRelation('eventType', $eventType);

        $this->assertSame(RecapFormService::TYPE_BEST, RecapFormService::resolveFromEvent($event));
    }

    public function test_event_type_recap_type_is_used_when_configured(): void
    {
        $eventType = new EventType(['recap_type' => RecapFormService::TYPE_SPECIAL_EVENT]);
        $event = new Event(['integrate_with_best' => 0]);
        $event->setRelation('eventType', $eventType);

        $this->assertSame(RecapFormService::TYPE_SPECIAL_EVENT, RecapFormService::resolveFromEvent($event));
    }

    public function test_missing_event_type_defaults_to_off_premise(): void
    {
        $event = new Event(['integrate_with_best' => 0]);
        $event->setRelation('eventType', null);

        $this->assertSame(RecapFormService::TYPE_OFF_PREMISE, RecapFormService::resolveFromEvent($event));
    }

    public function test_stored_recap_type_is_used_for_existing_recap(): void
    {
        $eventType = new EventType(['recap_type' => RecapFormService::TYPE_ON_PREMISE]);
        $event = new Event(['integrate_with_best' => 0]);
        $event->setRelation('eventType', $eventType);

        $recap = new Recap([
            'recap_type' => RecapFormService::TYPE_SPECIAL_EVENT,
        ]);
        $recap->setRelation('event', $event);

        $this->assertSame(RecapFormService::TYPE_SPECIAL_EVENT, RecapFormService::resolveForRecap($recap));
    }

    public function test_on_premise_rules_require_long_form_fields(): void
    {
        $rules = RecapFormService::typeSpecificRules(RecapFormService::TYPE_ON_PREMISE);

        $this->assertArrayHasKey('manager_comments', $rules);
        $this->assertStringContainsString('min:200', $rules['consumer_feedback']);
    }

    public function test_best_recap_sampling_requires_successful_true_response(): void
    {
        $service = new EventPushService($this->createMock(TokenService::class));

        $this->assertTrue($service->recapSamplingWasAccepted([
            ['Name' => 'event-38329', 'Successful' => true],
        ]));

        $this->assertFalse($service->recapSamplingWasAccepted([
            [
                'Name' => 'event-38329',
                'Message' => ['ConsumerEventSamplingTrigger failed'],
                'Successful' => false,
            ],
        ]));
    }
}
