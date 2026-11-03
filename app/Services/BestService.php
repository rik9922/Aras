<?php

namespace App\Services;

use App\Models\Event;
use App\Services\BEST\EventPushService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class BestService
{
    public function __construct(
        private readonly EventPushService $eventPushService,
    ) {}

    /**
     * Build the BEST payload for an Event.
     *
     * This is intentionally conservative: the BEST endpoint and schema requirements
     * can vary by tenant/env. We persist the payload + response for visibility and retries.
     */
    public function buildEventPayload(Event $event): array
    {
        $status = match ((string) ($event->event_status ?? '0')) {
            '1' => 'Completed',
            '2' => 'Canceled',
            default => 'Planned',
        };

        // BEST payload keys based on existing recap integration conventions.
        // If BEST requires more fields in your environment, add them here (we store payload+response for debugging).
        return [
            'Name' => (string) ($event->event_name ?? ('Event-'.$event->id)),
            'Status__c' => $status,
            'Target_Audience__c' => 'Consumer',
            'Objective__c' => (string) ($event->varietals ?? ''),
            'Description__c' => (string) ($event->notes ?? ''),
            'Consumer_Event_Lengths' => [
                [
                    'Event_Date__c' => optional($event->start_time)->format('Y-m-d'),
                    'Start_Time1__c' => optional($event->start_time)->format('H:i:s').'.000',
                    'End_Time__c' => optional($event->end_time)->format('H:i:s').'.000',
                ],
            ],
        ];
    }

    /**
     * Push an Event to BEST and return a structured result.
     *
     * @return array{ok:bool,http_status:int|null,response_json:mixed,response_body:string|null}
     */
    public function pushEvent(Event $event, array $payload): array
    {
        try {
            /** @var Response $response */
            $response = $this->eventPushService->pushSamplingEvent($payload);

            if ($response->failed()) {
                Log::error('BEST pushEvent non-2xx', [
                    'event_id' => $event->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return [
                'ok' => $response->successful(),
                'http_status' => $response->status(),
                'response_json' => $response->json(),
                'response_body' => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('BEST pushEvent exception', [
                'event_id' => $event->id,
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'http_status' => null,
                'response_json' => null,
                'response_body' => $e->getMessage(),
            ];
        }
    }
}

