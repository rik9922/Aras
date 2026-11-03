<?php

namespace App\Services;

use App\Models\AssignAmbassador;
use App\Models\Event;
use App\Models\Recap;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Shared ambassador recap listing queries used by the web portal and mobile API.
 * Keeps Create Recap and Submitted Recaps in sync across clients.
 */
class AmbassadorRecapListingService
{
    /**
     * Event IDs assigned to the ambassador that do not yet have a submitted recap.
     *
     * @return array<int, int|string>
     */
    public function openRecapEventIdsForAmbassador(int $ambassadorId): array
    {
        $assignAmb = AssignAmbassador::where('ambassador_id', $ambassadorId)
            ->pluck('event_id')
            ->toArray();

        $submittedId = Recap::where('ambassador_id', $ambassadorId)
            ->where('is_draft', false)
            ->pluck('event_id')
            ->toArray();

        return array_values(array_diff($assignAmb, $submittedId));
    }

    /**
     * Base query for events eligible to appear on Create Recap.
     */
    public function openRecapEventsQuery(int $ambassadorId, ?int $eventId = null): Builder
    {
        $query = Event::with('brand', 'location')
            ->whereIn('id', $this->openRecapEventIdsForAmbassador($ambassadorId))
            ->whereHas('markets', fn (Builder $marketQuery) => $marketQuery->whereIn('markets.id', $this->assignedMarketIdsForAmbassador($ambassadorId)))
            ->where('recap_needed', '1')
            ->where('event_status', '!=', '2')
            ->whereNotNull('start_time')
            ->orderBy('start_time', 'DESC')
            ->orderBy('id', 'DESC');

        if ($eventId !== null) {
            $query->where('id', $eventId);
        }

        return $query;
    }

    /**
     * @param  Collection<int, Event>  $events
     * @return Collection<int, Event>
     */
    public function filterEventsReadyForRecap(Collection $events): Collection
    {
        return $events
            ->filter(fn (Event $event) => $event->hasRecapStartTimePassed())
            ->sortByDesc(fn (Event $event) => $event->start_time)
            ->values();
    }

    /**
     * Events ready for recap creation, with timezone-aware start-time filtering.
     *
     * @return Collection<int, Event>
     */
    public function openRecapEvents(int $ambassadorId, ?int $eventId = null): Collection
    {
        return $this->filterEventsReadyForRecap(
            $this->openRecapEventsQuery($ambassadorId, $eventId)->get()
        );
    }

    /**
     * Event IDs with a fully submitted (non-draft) recap for this ambassador.
     *
     * @return array<int, int|string>
     */
    public function submittedRecapEventIdsForAmbassador(int $ambassadorId): array
    {
        return Recap::where('ambassador_id', $ambassadorId)
            ->where('is_draft', false)
            ->pluck('event_id')
            ->toArray();
    }

    /**
     * Base query for events that appear on Submitted Recaps.
     *
     * @param  array<int, string>  $relations
     */
    public function submittedRecapEventsQuery(
        int $ambassadorId,
        ?string $search = null,
        ?int $marketId = null,
        array $relations = ['brand', 'location', 'distributor', 'department', 'venueType', 'recap'],
    ): Builder {
        return Event::with($relations)
            ->whereIn('id', $this->submittedRecapEventIdsForAmbassador($ambassadorId))
            ->whereHas('markets', fn (Builder $marketQuery) => $marketQuery->whereIn('markets.id', $this->assignedMarketIdsForAmbassador($ambassadorId)))
            ->when($marketId, fn (Builder $query) => $query->where('market_id', $marketId))
            ->when($search !== null && $search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->orWhereHas('brand', fn (Builder $brandQuery) => $brandQuery
                        ->where('brand_name', 'LIKE', "%{$search}%"))
                        ->orWhereHas('location', fn (Builder $locationQuery) => $locationQuery
                            ->where('city', 'LIKE', "%{$search}%")
                            ->orWhere('address', 'LIKE', "%{$search}%"));
                });
            })
            ->orderByDesc('start_time')
            ->orderByDesc('id');
    }

    /** @return array<int, int> */
    private function assignedMarketIdsForAmbassador(int $ambassadorId): array
    {
        $user = User::query()
            ->with('ambassadorProfile.markets:id')
            ->find($ambassadorId);

        return $user?->ambassadorProfile?->markets?->pluck('id')
            ->map(fn ($id) => (int) $id)->all() ?? [];
    }
}
