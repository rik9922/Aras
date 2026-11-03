<?php

namespace App\Services\BEST;

use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EventPushService
{
    public const RECAP_SAMPLING_ACTION = 'consumer-events/sampling';

    protected $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    public function getAccount($select = 'Id,IsDeleted,MasterRecordId,Name', $top = 5000, $filter = '', $object = 'account')
    {
        $token = $this->tokenService->getToken();
        $response = Http::withToken($token)
            ->get(config('services.best.base_url').'/sobject?select='.$select.'&top='.$top.'&filter='.$filter.'&object='.$object);

        return $response->json();
    }

    public function getProgram($select = 'Id,IsDeleted,Name', $top = 1000, $filter = '', $object = 'program')
    {
        $token = $this->tokenService->getToken();
        $response = Http::withToken($token)
            ->get(config('services.best.base_url').'/sobject?select='.$select.'&top='.$top.'&filter='.$filter.'&object='.$object);

        return $response->json();
    }

    public function getConsumerEvents($select = 'Id,IsDeleted,Name', $top = 1000, $filter = '', $object = 'consumer_events')
    {
        $token = $this->tokenService->getToken();
        $response = Http::withToken($token)
            ->get(config('services.best.base_url').'/sobject?select='.$select.'&top='.$top.'&filter='.$filter.'&object='.$object);

        return $response->json();
    }

    public function getTags($select = 'Id,IsDeleted,Name', $top = 500, $filter = '', $object = 'tags')
    {
        $token = $this->tokenService->getToken();
        $response = Http::withToken($token)
            ->get(config('services.best.base_url').'/sobject?select='.$select.'&top='.$top.'&filter='.$filter.'&object='.$object);

        return $response->json();
    }

    public function getBrandFeatured($select = 'Id,IsDeleted,Name', $top = 10, $filter = '', $object = 'brand_featured')
    {
        $token = $this->tokenService->getToken();
        $response = Http::withToken($token)
            ->get(config('services.best.base_url').'/sobject?select='.$select.'&top='.$top.'&filter='.$filter.'&object='.$object);

        return $response->json();
    }

    public function pushSamplingEvent(array $eventPayload)
    {
        $token = $this->tokenService->getToken();

        $response = Http::withOptions([
            'verify' => (bool) config('services.best.verify_ssl', true),
        ])->withToken($token)
            ->post($this->recapSamplingEndpoint(), [$eventPayload]);

        return $response;
    }

    /**
     * Validation rules for ambassador recap submission when BEST integration is enabled.
     */
    public function recapBestValidationRules(): array
    {
        return [
            'event_name' => 'required|string|max:255',
            'status' => 'required|in:Planned,Completed,Canceled',
            'target_audience' => 'required|in:Consumer,Trade',
            'account_id' => 'required|string',
            'Associated_with_Demand_Creation_Station' => 'required|in:Yes,No',
            'wet_or_dry' => 'required|in:Wet,Dry',
            'objective' => 'required|string',
            'description' => 'required|string',
            'sampling_type' => 'required|in:Single Brand Sampling,Multi Brand Sampling',
            'attendees' => 'required|integer|min:0',
            'interactions' => 'required|integer|min:0',
            'event_results' => 'required|string',
            'key_learnings_future_considerations' => 'required|string',
            'consumer_feedback' => 'required|string',
            'account_feedback' => 'required|string',
            'budget' => 'required|integer|min:0',
            'Account_Premise_Type' => 'required|string',
            'program_id' => 'required|string',
            'tag_id' => 'required|string',
            '1l_bottles' => 'required|numeric|min:0',
            '175l_bottles' => 'required|numeric|min:0',
            '750ml_bottles' => 'required|numeric|min:0',
            '375ml_bottles' => 'required|numeric|min:0',
            'total_samples_distributed' => 'required|numeric|min:0',
            'total_samples_distributed_cocktail' => 'required|numeric|min:0',
            'total_samples_distributed_neat_shot' => 'required|numeric|min:0',
            'end_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ];
    }

    /**
     * Build the BEST consumer-events sampling payload from the recap request.
     */
    public function buildRecapSamplingPayloadFromRequest(Request $request): array
    {
        return [
            'Name' => $request->input('event_name'),
            'Status__c' => $request->input('status'),
            'Target_Audience__c' => $request->input('target_audience'),
            'Account__c' => (string) trim($request->input('account_id')),
            'Objective__c' => $request->input('objective'),
            'Description__c' => $request->input('description'),
            'Associated_with_Demand_Creation_Station__c' => $request->input('Associated_with_Demand_Creation_Station'),
            'Wet_or_Dry_Event__c' => $request->input('wet_or_dry'),
            'Sampling_Type__c' => $request->input('sampling_type'),
            'Attendees__c' => (int) $request->input('attendees'),
            'Interactions__c' => (int) $request->input('interactions'),
            'Event_Results__c' => $request->input('event_results'),
            'Key_Learnings_Future_Considerations__c' => $request->input('key_learnings_future_considerations'),
            'Consumer_Feedback__c' => $request->input('consumer_feedback'),
            'Account_Feedback__c' => $request->input('account_feedback'),
            'Consumer_Event_Samplings' => [
                [
                    'Budget__c' => (int) $request->input('budget') ?? 0,
                    'Total_Bottles_Sold_1L__c' => (float) $request->input('1l_bottles'),
                    'Total_Bottles_Sold_1_75L__c' => (float) $request->input('175l_bottles'),
                    'Total_Bottles_Sold_375ml__c' => (float) $request->input('375ml_bottles'),
                    'Total_Bottles_Sold_750ml__c' => (float) $request->input('750ml_bottles'),
                    'Total_Samples_Distributed_1__c' => (float) $request->input('total_samples_distributed'),
                    'Total_Samples_Distributed_Cocktail__c' => (float) $request->input('total_samples_distributed_cocktail'),
                    'Total_Samples_Distributed_Neat_Shot__c' => (float) $request->input('total_samples_distributed_neat_shot'),
                    'Account_Premise_Type__c' => $request->input('Account_Premise_Type'),
                    'Program_Name__c' => (string) trim($request->input('program_id')),
                    'Tag__c' => (string) trim($request->input('tag_id')),
                ],
            ],
            'Consumer_Event_Lengths' => [
                [
                    'Event_Date__c' => Carbon::parse($request->end_date)->format('Y-m-d'),
                    'Start_Time1__c' => Carbon::parse($request->start_time)->format('H:i:s').'.000',
                    'End_Time__c' => Carbon::parse($request->end_time)->format('H:i:s').'.000',
                ],
            ],
        ];
    }

    public function logRecapSamplingPayload(array $payload): void
    {
        Log::info('BEST recap sampling payload prepared', [
            'payload' => $this->safeRecapSamplingPayload($payload),
        ]);
    }

    /**
     * POST recap sampling payload to BEST (token + HTTP only; log payload separately to match original flow).
     */
    public function postRecapSamplingToBest(array $payload): Response
    {
        $token = $this->tokenService->getToken();

        return Http::withOptions([
            'verify' => (bool) config('services.best.verify_ssl', true),
        ])->withToken($token)
            ->post($this->recapSamplingEndpoint(), [$payload]);
    }

    public function recapSamplingEndpoint(): string
    {
        return rtrim(config('services.best.base_url'), '/').'/'.self::RECAP_SAMPLING_ACTION;
    }

    public function safeRecapSamplingPayload(array $payload): array
    {
        return $this->truncateForLog($payload);
    }

    public function safeResponseBody(?string $body): ?string
    {
        if ($body === null) {
            return null;
        }

        return mb_substr($body, 0, 4000);
    }

    public function recapSamplingWasAccepted(mixed $responseJson): bool
    {
        if (! is_array($responseJson) || $responseJson === []) {
            return false;
        }

        $items = array_is_list($responseJson) ? $responseJson : [$responseJson];
        foreach ($items as $item) {
            if (! is_array($item) || ($item['Successful'] ?? null) !== true) {
                return false;
            }
        }

        return true;
    }

    private function truncateForLog(array $payload): array
    {
        return collect($payload)
            ->map(function ($value) {
                if (is_array($value)) {
                    return $this->truncateForLog($value);
                }

                if (is_string($value)) {
                    return mb_substr($value, 0, 500);
                }

                return $value;
            })
            ->all();
    }
}
