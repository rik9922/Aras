<?php

namespace App\Services\Ambassador;

use App\Models\AmbassadorFirstShiftManagerAlert;
use App\Models\AssignAmbassador;
use App\Models\Event;
use App\Models\User;
use App\Services\NotificationDispatchService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FirstShiftManagerAlertService
{
    private const AMBASSADOR_ROLE_ID = 5;

    private const MANAGER_ROLE_ID = 6;

    private const TYPE = 'ba_first_shift_2d';

    public function __construct(
        private readonly NotificationDispatchService $notificationDispatchService
    ) {}

    /**
     * Notifies the Event Market Manager two calendar days before a Brand Ambassador's
     * first confirmed shift. Each ambassador is processed to completion at most once.
     */
    public function run(): array
    {
        if (! Schema::hasTable('em_ba_first_shift_alerts')) {
            return ['ok' => false, 'message' => 'Table em_ba_first_shift_alerts missing', 'notified' => 0, 'suppressed' => 0, 'pending' => 0];
        }

        if (! (bool) config('ambassador_first_shift_alerts.enabled', true)) {
            return ['ok' => true, 'message' => 'Disabled by config', 'notified' => 0, 'suppressed' => 0, 'pending' => 0];
        }

        $notified = 0;
        $suppressed = 0;
        $pending = 0;

        User::query()
            ->where('role_id', self::AMBASSADOR_ROLE_ID)
            ->where('status', '1')
            ->whereNotExists(static function ($q) {
                $q->selectRaw(1)
                    ->from('em_ba_first_shift_alerts as a')
                    ->whereColumn('a.ambassador_id', 'users.id');
            })
            ->orderBy('id')
            ->chunkById(100, function ($ambassadors) use (&$notified, &$suppressed, &$pending) {
                foreach ($ambassadors as $ambassador) {
                    $r = $this->processAmbassador($ambassador);
                    if ($r === 'notified') {
                        $notified++;
                    } elseif ($r === 'suppressed') {
                        $suppressed++;
                    } else {
                        $pending++;
                    }
                }
            });

        return [
            'ok' => true,
            'notified' => $notified,
            'suppressed' => $suppressed,
            'pending' => $pending,
        ];
    }

    private function processAmbassador(User $ambassador): string
    {
        $first = $this->firstConfirmedShiftAssignment($ambassador->id);
        if (! $first || ! $first->event) {
            return 'pending';
        }

        $event = $first->event;
        if (empty($event->start_time)) {
            $this->recordSuppressed(
                $ambassador,
                $event,
                now('UTC'),
                'no_start_time',
                null
            );

            return 'suppressed';
        }

        $tz = $event->timezone ?: config('app.timezone');
        $firstStart = Carbon::parse($event->start_time, $tz);
        $triggerDay = $firstStart->copy()->startOfDay()->subDays(2);
        $today = now($tz)->startOfDay();

        if (! $firstStart->isFuture()) {
            $this->recordSuppressed(
                $ambassador,
                $event,
                $firstStart,
                'first_shift_in_past',
                $this->resolveEventMarketManager($event)?->id
            );

            return 'suppressed';
        }

        if ($today->lessThan($triggerDay)) {
            return 'pending';
        }

        $manager = $this->resolveEventMarketManager($event);

        if ($today->equalTo($triggerDay)) {
            if (! $manager) {
                Log::warning('First shift manager alert: no Event Market Manager', [
                    'ambassador_id' => $ambassador->id,
                    'event_id' => $event->id,
                ]);
                $this->recordSuppressed(
                    $ambassador,
                    $event,
                    $firstStart,
                    'no_event_market_manager',
                    null
                );

                return 'suppressed';
            }

            $title = 'Brand Ambassador first shift in 2 days';
            $message = $ambassador->name.' has their first scheduled shift on '.
                $firstStart->format('M j, Y g:i A T')." ({$event->event_name})";

            $this->notificationDispatchService->dispatch(
                null,
                (int) $manager->id,
                self::TYPE,
                $title,
                $message,
                null,
                null
            );

            $this->recordNotified($ambassador, $event, $firstStart, (int) $manager->id);

            return 'notified';
        }

        $this->recordSuppressed(
            $ambassador,
            $event,
            $firstStart,
            'trigger_window_missed',
            $manager?->id
        );

        return 'suppressed';
    }

    private function firstConfirmedShiftAssignment(int $ambassadorId): ?AssignAmbassador
    {
        return AssignAmbassador::query()
            ->where('ambassador_id', $ambassadorId)
            ->where('status', '1')
            ->whereHas('event', function ($q) {
                $q->where('event_status', '!=', '2')
                    ->whereNotNull('start_time');
            })
            ->with('event')
            ->get()
            ->filter(static fn (AssignAmbassador $a) => $a->event !== null)
            ->sortBy(static function (AssignAmbassador $a) {
                $e = $a->event;
                $tz = $e->timezone ?: config('app.timezone');

                return Carbon::parse($e->start_time, $tz)->getTimestamp();
            })
            ->first();
    }

    public function resolveEventMarketManager(Event $event): ?User
    {
        if ($event->manager_id) {
            $m = $event->manager;
            if ($m && (int) $m->role_id === self::MANAGER_ROLE_ID && (string) $m->status === '1') {
                return $m;
            }
        }

        if (! $event->market_id) {
            return null;
        }

        $event->loadMissing('market.managers');
        $market = $event->market;
        if (! $market) {
            return null;
        }

        return $market->managers()
            ->where('status', '1')
            ->orderBy('id')
            ->first();
    }

    private function recordNotified(User $ambassador, Event $event, Carbon $firstStart, int $managerId): void
    {
        AmbassadorFirstShiftManagerAlert::create([
            'ambassador_id' => $ambassador->id,
            'first_shift_event_id' => $event->id,
            'first_shift_at' => $firstStart->copy()->setTimezone('UTC'),
            'market_manager_id' => $managerId,
            'notified_at' => now(),
        ]);
    }

    private function recordSuppressed(
        User $ambassador,
        Event $event,
        Carbon $firstStart,
        string $reason,
        ?int $managerId
    ): void {
        AmbassadorFirstShiftManagerAlert::create([
            'ambassador_id' => $ambassador->id,
            'first_shift_event_id' => $event->id,
            'first_shift_at' => $firstStart->copy()->setTimezone('UTC'),
            'market_manager_id' => $managerId,
            'notified_at' => null,
            'suppressed_reason' => $reason,
            'suppressed_at' => now(),
        ]);
    }
}
