<?php

namespace App\Jobs;

use App\Models\Event;
use App\Services\BestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendEventToBestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Fail fast on repeated transient errors; spaced retries help with rate limits.
     */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly int $eventId,
        public readonly bool $forceRetry = false,
    ) {}

    public function handle(BestService $best): void
    {
        $event = DB::transaction(function () {
            /** @var Event|null $locked */
            $locked = Event::query()->whereKey($this->eventId)->lockForUpdate()->first();
            if (! $locked) {
                return null;
            }

            // Idempotency / duplicate prevention.
            if (! $this->forceRetry && ! is_null($locked->best_synced_at)) {
                return null;
            }

            // If we are retrying manually, clear previous failure marker but preserve payload/response history.
            $locked->best_status = 'processing';
            $locked->save();

            return $locked;
        });

        if (! $event) {
            return;
        }

        $payload = $best->buildEventPayload($event);

        // Persist payload for audit/debugging.
        Event::query()->whereKey($event->id)->update([
            'best_payload' => json_encode($payload),
        ]);

        $result = $best->pushEvent($event, $payload);

        if (($result['ok'] ?? false) === true) {
            Event::query()->whereKey($event->id)->update([
                'best_synced_at' => now(),
                'best_status' => 'success',
                'best_response' => json_encode([
                    'ok' => true,
                    'result' => $result,
                ]),
            ]);

            return;
        }

        Event::query()->whereKey($event->id)->update([
            'best_status' => 'failed',
            'best_response' => json_encode([
                'ok' => false,
                'result' => $result,
            ]),
        ]);

        // Throw so Laravel retries (tries/backoff apply).
        $status = $result['http_status'] ?? null;
        $body = $result['response_body'] ?? null;
        $bodyPreview = is_string($body) ? mb_substr($body, 0, 800) : null;
        throw new \RuntimeException('BEST push failed for event '.$event->id.' (status='.($status ?? 'n/a').')'.($bodyPreview ? ' body='.$bodyPreview : ''));
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendEventToBestJob failed', [
            'event_id' => $this->eventId,
            'message' => $e->getMessage(),
        ]);
    }
}

