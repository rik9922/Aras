<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\NormalizesBookedByEmails;
use App\Models\AssignAmbassador;
use App\Models\Attachement;
use App\Models\Brand;
use App\Models\Event;
use App\Models\EventBillingParty;
use App\Models\EventDepartment;
use App\Models\EventDistributor;
use App\Models\EventLocation;
use App\Models\EventType;
use App\Models\Market;
use App\Models\User;
use App\Models\Venue;
use App\Jobs\SendEventToBestJob;
use App\Services\BEST\EventPushService;
use App\Services\BEST\TokenService;
use App\Services\ClientBudgetService;
use App\Services\EventLocationService;
use App\Services\EventTimezoneService;
use App\Services\AmbassadorStaffingService;
use App\Services\EventService;
use App\Services\NotificationDispatchService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    use NormalizesBookedByEmails;

    protected $eventService;

    protected $tokenService;

    public function __construct(EventService $eventService, TokenService $tokenService)
    {
        $this->eventService = $eventService;
        $this->tokenService = $tokenService;
    }

    protected function managerExtraPayBudgetViolation(
        int $managerId,
        ?int $marketId,
        ?int $excludeEventId,
        array $overridePayData
    ): ?string {
        if (! $marketId) {
            return null;
        }

        $budget = DB::table('manager_market_user')
            ->where('user_id', $managerId)
            ->where('market_id', $marketId)
            ->value('extra_pay_budget');

        $budget = $budget !== null ? (float) $budget : null;
        if ($budget === null || $budget <= 0) {
            return null;
        }

        $newExtra = 0.0;
        foreach ($overridePayData as $v) {
            if (! is_numeric($v)) {
                continue;
            }
            $newExtra += (float) $v;
        }
        $newExtra = round($newExtra, 2);

        $usedQuery = DB::table('assign_ambassadors as aa')
            ->join('events as e', 'e.id', '=', 'aa.event_id')
            ->where('e.manager_id', $managerId)
            ->where('e.market_id', $marketId);

        if ($excludeEventId) {
            $usedQuery->where('e.id', '!=', $excludeEventId);
        }

        $used = (float) ($usedQuery->sum('aa.override_pay') ?? 0);
        $projected = round($used + $newExtra, 2);

        if ($projected <= $budget + 0.00001) {
            return null;
        }

        $remaining = max(0, round($budget - $used, 2));
        return 'Additional Pay exceeds the manager market budget. Remaining: $'.number_format($remaining, 2).'.';
    }

    public function index(Request $request)
    {
        $this->markEndedEventsCompletedForWeb();

        // NOTE: This page renders many filter dropdowns. Loading entire tables (esp. users)
        // can exceed PHP memory (128MB) when datasets grow large.
        // Keep initial datasets bounded; the table itself is loaded via pagination/AJAX.
        $states = app(\App\Services\LocationStateService::class)->options();

        $locations = EventLocation::query()
            ->select(['id', 'name', 'address', 'city', 'state'])
            ->orderBy('name')
            ->orderBy('address')
            ->limit(500)
            ->get();

        $eventTypes = EventType::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->limit(250)
            ->get();

        $venueTypes = Venue::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->limit(500)
            ->get();

        $departments = EventDepartment::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->limit(250)
            ->get();

        $distributors = EventDistributor::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->limit(500)
            ->get();

        $brands = Brand::query()
            ->select(['id', 'brand_name'])
            ->orderBy('brand_name')
            ->limit(500)
            ->get();

        $managers = User::query()
            ->select(['id', 'name'])
            ->where('role_id', 6)
            ->where('status', '1')
            ->orderBy('name')
            ->limit(500)
            ->get();

        $ambassadors = User::query()
            ->select(['id', 'name', 'email'])
            ->where('role_id', 5)
            ->orderBy('name')
            ->limit(1000)
            ->get();

        $markets = Market::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->limit(500)
            ->get();

        $clients = User::query()
            ->select(['id', 'name'])
            ->where('role_id', 3)
            ->orderBy('name')
            ->limit(1000)
            ->get();
        // BUG-12 (MC cmon1a7u4005d3r1h5cdct2vh): events list was ordered by id DESC,
        // which surfaces newest-created events at top. Order by event date (start_time)
        // DESC instead so upcoming/most-recent events show first; fall back to id DESC
        // for events with no start_time set so they don't disappear off the bottom.
        $query = Event::with('distributor', 'department', 'venueType', 'brand', 'location', 'markets')
            ->withCount(['pendingAmbassadorApplications as pending_applicants_count'])
            // Previous requirement (chronological ascending — kept for easy restore):
            // ->orderByRaw('booked_date IS NULL, booked_date DESC, start_time ASC');
            // Latest client/PM requirement — descending by event date (farthest/newest first):
            ->orderByRaw('start_time IS NULL, start_time DESC, id DESC');

        if (! $request->boolean('show_completed')) {
            $query->where('event_status', '!=', '1');
        } else {
            $query->where('event_status', '1');
        }

        $events = $query->paginate(20);

        $eventFilterOptions = Event::query()
            ->select(['id', 'event_name'])
            ->orderByRaw('start_time IS NULL, start_time DESC, id DESC')
            ->limit(2000)
            ->get();

        $getEventHours = $this->eventService->getTotalEventHour();

        if ($request->ajax()) {
            $query = Event::query();

            // inputs and select filters

            // Department filter
            if ($request->filled('department_id')) {
                $query->where('department_id', $request->department_id);
            }

            // Market filter
            if ($request->filled('market_id')) {
                $marketId = (int) $request->market_id;
                $query->whereHas('markets', fn ($q) => $q->where('markets.id', $marketId));
            }

            // Date range filter - only when both dates are provided
            if ($request->filled('startDate') && $request->filled('endDate')) {
                $startDate = $request->startDate;
                $endDate = $request->endDate;

                $query->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_time', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                        ->orWhereBetween('end_time', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                        ->orWhere(function ($subQ) use ($startDate, $endDate) {
                            $subQ->where('start_time', '<=', $startDate.' 00:00:00')
                                ->where('end_time', '>=', $endDate.' 23:59:59');
                        });
                });
            }

            // Manager filter
            if ($request->filled('manager_id')) {
                $query->where('manager_id', $request->manager_id);
            }

            // event type
            if ($request->filled('event_type_id')) {
                $query->where('event_type_id', $request->event_type_id);
            }

            // distributer
            if ($request->filled('distributor_id')) {
                $query->where('distributor_id', $request->distributor_id);
            }

            // event id
            if ($request->filled('event_id')) {
                $query->where('id', $request->event_id);
            }

            // location state
            if ($request->filled('location_state')) {
                $aliases = app(\App\Services\LocationStateService::class)->aliases($request->input('location_state'));
                $query->whereHas('location', fn ($q) => $q->whereIn(\Illuminate\Support\Facades\DB::raw('UPPER(TRIM(state))'), $aliases));
            }

            if ($request->filled('location')) {
                $query->where('event_location_id', $request->location);
            }

            // ambassador name
            if ($request->filled('ambassador_name')) {
                $ambassadorId = $request->ambassador_name;
                $query->whereRaw('FIND_IN_SET(?, ambassador_id)', [$ambassadorId]);
            }

            // ambassador email
            if ($request->filled('ambassador_email')) {
                $ambassadorId = $request->ambassador_email;
                $query->whereRaw('FIND_IN_SET(?, ambassador_id)', [$ambassadorId]);
            }

            // varietals
            if ($request->filled('varietals')) {
                $query->where('varietals', 'like', '%'.$request->varietals.'%');
            }

            // payment type
            if ($request->filled('payment_type')) {
                $pt = $request->payment_type;
                $legacy = [
                    'No purchase' => ['1'],
                    'Product on site' => ['2'],
                    'Comdata' => ['3', 'ComData'],
                    'Check' => ['4'],
                    'Reimbursement' => ['5'],
                ];
                $candidates = array_values(array_unique(array_merge([$pt], $legacy[$pt] ?? [])));
                $query->whereIn('payment_type', $candidates);
            }

            // brand
            if ($request->filled('brand_id')) {
                $query->forBrandOrDescendants($request->brand_id);
            }

            // region
            if ($request->filled('region')) {
                $query->where('region', $request->region);
            }

            // billto (single client_id or any billing party)
            if ($request->filled('bill_id')) {
                $query->where(function ($q) use ($request) {
                    $q->where('client_id', $request->bill_id)
                        ->orWhereHas('billingParties', function ($bq) use ($request) {
                            $bq->where('client_id', $request->bill_id);
                        });
                });
            }

            // filter checkbox options

            // Include Nearby Events
            if ($request->has('include_nearby_events') && $request->include_nearby_events == 1) {
                $query->where('is_nearby', 1); // Adjust based on your DB structure
            }

            // Overview Needed
            if ($request->has('overview_needed') && $request->overview_needed == 1) {
                $query->where('recap_needed', 1);
            }

            // Not Fully Staffed
            if ($request->has('not_fully_staffed') && $request->not_fully_staffed == 1) {
                $query->whereRaw('(SELECT COUNT(*) FROM assign_ambassadors WHERE assign_ambassadors.event_id = events.id AND assign_ambassadors.status = ?) < COALESCE(NULLIF(events.position, 0), 1)', ['1']);
            }

            // Filter by kit_required (1 or 0)
            if ($request->has('kit_required') && $request->kit_required == 1) {
                $query->where('kit', 1);
            }

            // Exclude Cancellations
            if ($request->has('exclude_cancellations') && $request->exclude_cancellations == 1) {
                $query->where('event_status', '!=', 2); // Assuming 2 = cancelled
            }

            $backupBrandFilter = ($request->has('backup_brand_used') && $request->backup_brand_used == 1)
                || $request->filled('backup_brand_name');
            if ($backupBrandFilter) {
                $query->whereHas('recap', function ($q) use ($request) {
                    if ($request->has('backup_brand_used') && $request->backup_brand_used == 1) {
                        $q->where('backup_brand_used', 1);
                    }
                    if ($request->filled('backup_brand_name')) {
                        $q->where('backup_brand_name', 'like', '%'.$request->backup_brand_name.'%');
                    }
                });
            }

            // Show Only Cancellations, Pending, or Completed (radio buttons)
            if ($request->filled('event_status')) {
                $query->where('event_status', $request->event_status);
            } elseif ($request->boolean('show_completed')) {
                $query->where('event_status', '1');
            } elseif (! $request->filled('event_id')) {
                // Keep completed events hidden in the default list, but allow an
                // exact Event ID lookup to find a completed event.
                $query->where('event_status', '!=', '1');
            }

            // BEST sync status filter
            if ($request->filled('best_sync')) {
                $best = (string) $request->best_sync;
                if ($best === 'synced') {
                    $query->whereNotNull('best_synced_at');
                } elseif ($best === 'not_synced') {
                    $query->whereNull('best_synced_at');
                } elseif ($best === 'failed') {
                    $query->where('best_status', 'failed');
                } elseif ($best === 'pending') {
                    $query->where('best_status', 'pending')->whereNull('best_synced_at');
                }
            }

            $events = $query->with('distributor', 'department', 'venueType', 'brand', 'location', 'markets')
                ->withCount(['pendingAmbassadorApplications as pending_applicants_count'])
                // Previous requirement (chronological ascending — kept for easy restore):
                // ->orderByRaw('booked_date IS NULL, booked_date DESC, start_time ASC')
                // Latest client/PM requirement — descending by event date (farthest/newest first):
                ->orderByRaw('start_time IS NULL, start_time DESC, id DESC')
                ->paginate(20)
                ->appends($request->all());

            return response()->json([
                'table_html' => view('admin.event.partials.event-table', compact('events'))->render(),
                'pagination' => $events->links('vendor.pagination.custom')->toHtml(),
                'total' => $events->total(),
                'status' => 200,
            ]);
        }

        return view('admin.event.index', compact(
            'events', 'eventFilterOptions', 'markets', 'managers', 'brands', 'distributors',
            'departments', 'eventTypes', 'venueTypes', 'locations', 'states', 'ambassadors',
            'clients', 'getEventHours'
        ));
    }

    private function markEndedEventsCompletedForWeb(): void
    {
        Event::query()
            ->whereNotNull('end_time')
            ->where(function ($query) {
                $query->whereNull('event_status')
                    ->orWhereNotIn('event_status', ['1', '2']);
            })
            ->select(['id', 'end_time', 'timezone', 'event_status'])
            ->chunkById(200, function ($events) {
                foreach ($events as $event) {
                    if (! $this->hasEventEnded($event)) {
                        continue;
                    }

                    $event->event_status = '1';
                    $event->saveQuietly();
                }
            });
    }

    private function hasEventEnded(Event $event): bool
    {
        if (empty($event->end_time)) {
            return false;
        }

        $eventTimezone = app(EventTimezoneService::class)->normalizeIana($event->timezone);
        $end = Carbon::parse($event->end_time, $eventTimezone);

        return Carbon::now($eventTimezone)->greaterThanOrEqualTo($end);
    }

    /**
     * Bulk dispatch: send selected events to BEST sequentially (chains, chunked).
     */
    public function sendSelectedToBest(Request $request)
    {
        $ids = $request->input('event_ids', []);
        if (is_string($ids)) {
            $ids = preg_split('/[\s,]+/', $ids, -1, PREG_SPLIT_NO_EMPTY);
        }

        $ids = collect($ids)
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return back()->with('error', 'No events selected.');
        }

        // Prevent duplicates: only dispatch for not-yet-synced.
        $dispatchIds = Event::query()
            ->whereIn('id', $ids->all())
            ->where('integrate_with_best', 1)
            ->whereNull('best_synced_at')
            ->pluck('id');

        $chunkSize = 100;
        $dispatched = 0;

        foreach ($dispatchIds->chunk($chunkSize) as $chunk) {
            $jobs = $chunk->map(fn ($id) => new SendEventToBestJob((int) $id))->all();
            Bus::chain($jobs)->dispatch();
            $dispatched += count($jobs);
        }

        return back()->with('success', "Dispatched {$dispatched} event(s) to BEST queue.");
    }

    /**
     * Manual retry for failed events.
     */
    public function retryBest(Request $request, Event $event)
    {
        if (! is_null($event->best_synced_at)) {
            return back()->with('success', 'Event already synced to BEST.');
        }

        if ((string) $event->best_status !== 'failed') {
            return back()->with('success', 'Event is not in failed status.');
        }

        SendEventToBestJob::dispatch($event->id, true);

        return back()->with('success', 'Retry dispatched.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $states = EventLocation::orderBy('name')->get();
        $locations = User::where('role_id', 4)->get();
        $clients = User::where('role_id', 3)->get();
        $venueTypes = Venue::get();
        $departments = EventDepartment::get();
        $distributors = EventDistributor::get();
        $brands = Brand::get();
        $managers = User::where('role_id', '6')->where('status', '1')->get();
        $markets = Market::get();
        $eventTypes = EventType::get();
        $nextEventName = $this->generateEventNameFromNextId();

        return view('admin.event.create', compact('locations', 'clients', 'departments', 'venueTypes', 'distributors', 'brands', 'managers', 'markets', 'eventTypes', 'states', 'nextEventName'));
    }

    /**
     * Duplicate an existing event by pre-filling the create form.
     * This does NOT persist anything until the user submits the create form.
     */
    public function duplicate($id)
    {
        $event = Event::with(['markets', 'billingParties', 'primaryBillingParty'])
            ->findOrFail($id);

        $billingPartyIds = $event->billingParties->pluck('client_id')->map(fn ($v) => (int) $v)->values()->all();
        if (empty($billingPartyIds) && $event->client_id) {
            $billingPartyIds = [(int) $event->client_id];
        }

        $primaryBillingPartyId = (int) ($event->primaryBillingParty?->client_id ?? $event->client_id ?? 0);
        if ($primaryBillingPartyId && ! in_array($primaryBillingPartyId, $billingPartyIds, true)) {
            array_unshift($billingPartyIds, $primaryBillingPartyId);
        }

        $marketIds = $event->markets->pluck('id')->map(fn ($v) => (int) $v)->values()->all();
        if (empty($marketIds) && $event->market_id) {
            $marketIds = [(int) $event->market_id];
        }

        // Prefill assigned ambassadors + override pay (uses same mechanism as validation failure re-hydration).
        $assigned = AssignAmbassador::query()
            ->where('event_id', (int) $event->id)
            ->where('status', '1')
            ->get(['ambassador_id', 'override_pay']);

        $ambassadorIds = $assigned->pluck('ambassador_id')->map(fn ($v) => (int) $v)->values()->all();
        $overridePayMap = [];
        foreach ($assigned as $row) {
            $overridePayMap[(int) $row->ambassador_id] = (int) ($row->override_pay ?? 0);
        }

        $bookedBy = $event->booked_by;
        $bookedByValues = [];
        if (is_array($bookedBy)) {
            $bookedByValues = $bookedBy;
        } else {
            try {
                $decoded = json_decode((string) $bookedBy, true);
                $bookedByValues = is_array($decoded) ? $decoded : [trim((string) $bookedBy)];
            } catch (\Throwable $e) {
                $bookedByValues = [trim((string) $bookedBy)];
            }
        }
        $bookedByValues = array_values(array_filter(array_map('trim', $bookedByValues), fn ($v) => $v !== ''));
        $nextEventName = $this->generateEventNameFromNextId();

        $prefill = [
            'auto_event_name' => $nextEventName,
            'event_name' => $nextEventName,
            'event_type_id' => $event->event_type_id,
            'event_location_id' => $event->event_location_id,
            'venue_type_id' => $event->venue_type_id,

            'department_id' => $event->department_id,
            'position' => $event->position,
            'age_restricted' => (int) ($event->age_restricted ?? 0),
            'recap_needed' => (int) ($event->recap_needed ?? 0),

            'distributor_id' => $event->distributor_id,
            'brand_id' => $event->brand_id,
            'market_ids' => $marketIds,
            'manager_id' => $event->manager_id,

            'timezone' => $event->timezone ?: 'UTC',
            'timezone_label' => $event->timezone_label,
            'start_time' => $event->start_time,
            'end_time' => $event->end_time,

            'varietals' => $event->varietals,
            'notes' => $event->notes,
            'internal_notes' => $event->internal_notes,

            'payment_status' => (int) ($event->payment_status ?? 0),
            'expedite_fee' => $event->expedite_fee,
            'shipping_fee' => $event->shipping_fee,
            'parking_fee' => $event->parking_fee,
            'drive_time' => $event->drive_time,

            'payment_type' => $event->payment_type,
            'receipt' => (int) ($event->receipt ?? 0),
            'kit' => (int) ($event->kit ?? 0),
            'uniform' => $event->uniform,

            'billing_party_ids' => $billingPartyIds,
            'primary_billing_party_id' => $primaryBillingPartyId ?: null,
            'client_id' => $primaryBillingPartyId ?: null,
            'split_cups' => (int) ($event->split_cups ?? 0),
            'labor_amount' => $event->labor_amount,
            'buyback_amount' => $event->buyback_amount,
            'cups_amount' => $event->cups_amount,

            'pinata_pay' => (int) ($event->pinata_pay ?? 0),
            'integrate_with_best' => (int) ($event->integrate_with_best ?? 0),

            'region' => $event->region,
            'booked_date' => $event->booked_date,
            'booked_by' => $bookedByValues,

            'selectedAmbassadorIds' => implode(',', $ambassadorIds),
            'overridePayData' => json_encode($overridePayMap),
        ];

        $prefix = (int) (auth()->user()->role_id ?? 0) === 6 ? 'manager' : 'admin';

        return redirect()
            ->route($prefix . '.event.create')
            ->withInput($prefill)
            ->with('success', "Event #{$event->id} duplicated into a new draft. Review and click Save to create a new event.");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, EventPushService $eventPushService, ClientBudgetService $budget)
    {
        $request->merge(['booked_by' => $this->normalizeBookedByEmails($request->input('booked_by'))]);

        $validated = $request->validate([
            'event_name' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'event_address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'event_type_id' => 'required|exists:event_types,id',
            'event_location_id' => 'required|exists:event_locations,id',
            'venue_type_id' => 'nullable|exists:venues,id',
            'client_id' => 'required_without:billing_party_ids|nullable|exists:users,id',
            'billing_party_ids' => 'nullable|array',
            'billing_party_ids.*' => 'exists:users,id',
            'primary_billing_party_id' => 'nullable|exists:users,id',
            'department_id' => 'required|exists:event_departments,id',
            'position' => 'required|integer|min:1',
            'age_restricted' => 'boolean',
            'recap_needed' => 'boolean',
            'distributor_id' => 'required|exists:event_distributors,id',
            'brand_id' => 'required|exists:brands,id',
            'market_ids' => 'required|array|min:1',
            'market_ids.*' => 'exists:markets,id',
            'manager_id' => 'required|exists:users,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date',
            'varietals' => 'required|string',
            'notes' => 'required|string',
            'internal_notes' => 'nullable|string',
            'expedite_fee' => 'nullable|integer',
            'shipping_fee' => 'nullable|integer',
            'parking_fee' => 'nullable|integer',
            'drive_time' => 'nullable|integer',
            'payment_type' => 'required',
            'uniform' => 'nullable|string',
            'booked_by' => ['required', 'array', 'min:1'],
            'booked_by.*' => ['email'],
            'booked_date' => 'required|date',
            'region' => 'required|string|in:Southeast,Midwest',
            'payment_status' => 'boolean',
            'receipt' => 'boolean',
            'kit' => 'boolean',
            'pinata_pay' => 'boolean',
            'integrate_with_best' => 'boolean',
            'best_event_data' => 'nullable|string',
            'is_thc_event' => 'boolean',
            'split_cups' => 'boolean',
            'labor_amount' => 'nullable|numeric|min:0',
            'labor_rates' => 'nullable|array',
            'labor_rates.*' => 'nullable|numeric|min:0',
            'buyback_amount' => 'nullable|numeric|min:0',
            'cups_amount' => 'nullable|numeric|min:0',
        ], [
            'event_type_id.required'     => 'Event Type is required.',
            'event_type_id.exists'       => 'The selected Event Type is invalid.',
            'event_location_id.required'  => 'Venue is required.',
            'event_location_id.exists'    => 'The selected Venue is invalid.',
            'client_id.required_without'  => 'Client (Billing Party) is required.',
            'department_id.required'      => 'Department is required.',
            'position.required'           => 'Positions is required.',
            'position.integer'            => 'Positions must be a whole number.',
            'position.min'                => 'Positions must be at least 1.',
            'distributor_id.required'     => 'Distributor is required.',
            'brand_id.required'           => 'Brand is required.',
            'market_ids.required'         => 'At least one Market is required.',
            'market_ids.min'              => 'At least one Market is required.',
            'manager_id.required'         => 'Manager is required.',
            'start_time.required'         => 'Start Date is required.',
            'start_time.date'             => 'Start Date must be a valid date.',
            'end_time.required'           => 'End Date is required.',
            'end_time.date'               => 'End Date must be a valid date.',
            'varietals.required'          => 'Varietals is required.',
            'notes.required'              => 'Notes is required.',
            'payment_type.required'       => 'Payment Type is required.',
            'booked_by.required'          => 'Booked By is required.',
            'booked_by.*.email'           => 'Each Booked By entry must be a valid email address.',
            'booked_date.required'        => 'Booked Date is required.',
            'booked_date.date'            => 'Booked Date must be a valid date.',
            'region.required'             => 'Region is required.',
            'region.in'                   => 'Region must be Southeast or Midwest.',
        ]);

        $validated['booked_by'] = json_encode($request->input('booked_by'));

        $bestResult = $this->applyBestEventDataToValidated($request, $validated);
        if ($bestResult instanceof \Illuminate\Http\RedirectResponse) {
            return $bestResult;
        }
        $validated = $bestResult;

        $validated['payment_type'] = $this->normalizePaymentType($validated['payment_type'] ?? null);
        $locationService = app(EventLocationService::class);
        $validated = $locationService->applyEventLocationFromVenue($validated);
        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['event_location_id' => 'Selected venue is missing coordinates. Update the venue address first.']);
        }

        $marketIds = collect($validated['market_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();
        // Keep legacy events.market_id populated for compatibility with older queries/views.
        $validated['market_id'] = $marketIds[0] ?? null;

        // These are not columns on `events`; they are used to sync `event_billing_parties`.
        unset($validated['billing_party_ids'], $validated['primary_billing_party_id'], $validated['market_ids'], $validated['labor_rates']);

        $eventType = EventType::find($request->event_type_id);

        if ($eventType && $eventType->name === 'Portal THC Event') {
            $validated['is_thc_event'] = 1;
        }

        $validated['apply_event'] = ($request->apply_event === 'on') ? 1 : 0;
        // ambassador_id is a NOT NULL string column (comma-separated IDs); default to '' not null.
        $validated['ambassador_id'] = $request->selectedAmbassadorIds ?: '';
        $validated['split_cups'] = $request->boolean('split_cups');
        // Some environments enforce NOT NULL on these columns; default safely to 0.
        $validated['labor_amount'] = $request->filled('labor_amount') ? $request->labor_amount : 0;
        $validated['buyback_amount'] = $request->filled('buyback_amount') ? $request->buyback_amount : 0;
        $validated['cups_amount'] = $request->filled('cups_amount') ? $request->cups_amount : 0;

        $billingPartyIds = $request->input('billing_party_ids', []);
        if (! empty($billingPartyIds)) {
            $primaryId = $request->input('primary_billing_party_id') ?? $billingPartyIds[0];
            $validated['client_id'] = in_array($primaryId, $billingPartyIds) ? $primaryId : $billingPartyIds[0];
        }

        $labor = (float) ($validated['labor_amount'] ?? 0);
        $buyback = (float) ($validated['buyback_amount'] ?? 0);
        $cups = (float) ($validated['cups_amount'] ?? 0);
        $newStatus = (string) ($validated['event_status'] ?? '0');
        $primaryClientId = (int) ($validated['client_id'] ?? 0);
        if ($primaryClientId && $newStatus !== '2') {
            $billClient = User::find($primaryClientId);
            if ($billClient) {
                $eval = $budget->evaluateTrackedEvent($billClient, $labor, $buyback, $cups, null);
                if ($eval['pending']) {
                    return redirect()->back()->withInput()->with('error', $eval['message'] ?? 'Budget approval is required before creating this over-limit event.');
                }
                if (! $eval['allowed']) {
                    return redirect()->back()->withInput()->with('error', $eval['message'] ?? 'Cannot create this event due to budget limits.');
                }
            }
        }

        $overridePayData = json_decode($request->input('overridePayData'), true);
        if (! is_array($overridePayData)) {
            $overridePayData = [];
        }
        $extraViolation = $this->managerExtraPayBudgetViolation(
            (int) $validated['manager_id'],
            $validated['market_id'] ? (int) $validated['market_id'] : null,
            null,
            $overridePayData
        );
        if ($extraViolation) {
            return redirect()->back()->withInput()->with('error', $extraViolation);
        }

        $requestedEventName = trim((string) ($validated['event_name'] ?? ''));
        $autoEventName = trim((string) $request->input('auto_event_name', ''));
        $shouldAutoGenerateEventName = $requestedEventName === '' || ($autoEventName !== '' && $requestedEventName === $autoEventName);
        if ($shouldAutoGenerateEventName) {
            $validated['event_name'] = $this->generateEventNameFromNextId();
        }

        // Save validated data
        $newEvent = Event::create($validated);
        if ($shouldAutoGenerateEventName) {
            $newEvent->event_name = $this->generateEventName($newEvent->id);
            $newEvent->save();
        }
        $newEvent->markets()->sync($marketIds);

        $this->syncEventBillingParties($newEvent, $request);
        $newEvent->labor_amount = $this->calculateLaborAmount($newEvent);
        $newEvent->save();

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $mimeType = $file->getMimeType();
                $destination = public_path('attachments');
                if (! file_exists($destination)) {
                    mkdir($destination, 0755, true);
                }

                $file->move($destination, $filename);

                $pathName = 'attachments/'.$filename;

                $attachment = new Attachement;
                $attachment->event_id = $newEvent->id;
                $attachment->filename = $pathName;
                $attachment->driver = 1;
                $attachment->type = $mimeType;
                $attachment->save();
            }
        }

        $staffingService = app(AmbassadorStaffingService::class);
        $staffingRows = $staffingService->decodeStaffingPayload($request->input('ambassador_staffing_data'));
        $eventPinataPay = $request->boolean('pinata_pay');

        if (! empty($staffingRows)) {
            $staffingService->syncStaffingFromForm($newEvent, $staffingRows, $eventPinataPay, []);
        } elseif (! empty($request->selectedAmbassadorIds)) {
            $ambassadorIds = array_filter(array_map('intval', explode(',', (string) $request->selectedAmbassadorIds)));
            $overridePayData = json_decode($request->input('overridePayData'), true);
            $reasonData = json_decode($request->input('additionalPayReasonData'), true);
            $noteData = json_decode($request->input('additionalPayNoteData'), true);
            $staffingService->attachNewAmbassadors(
                $newEvent,
                $ambassadorIds,
                is_array($overridePayData) ? $overridePayData : [],
                is_array($reasonData) ? $reasonData : [],
                is_array($noteData) ? $noteData : [],
                $eventPinataPay,
                true
            );
        }

        $routePrefix = ((int) (auth()->user()->role_id ?? 0) === 6) ? 'manager' : 'admin';
        return redirect()->route($routePrefix . '.events')->with('success', 'EVENT ID '.$newEvent->id.' created');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $states = EventLocation::orderBy('name')->get();
        $event = Event::with('distributor', 'department', 'venueType', 'brand', 'location', 'eventAttachments', 'billingParties.client')->where('id', $id)->first();
        $locations = User::where('role_id', 4)->get();
        $clients = User::where('role_id', 3)->get();
        $venueTypes = Venue::get();
        $departments = EventDepartment::get();
        $distributors = EventDistributor::get();
        $brands = Brand::get();
        $managers = User::where('role_id', '6')->where('status', '1')->get();
        $markets = Market::get();
        $eventTypes = EventType::get();

        $staffing = app(AmbassadorStaffingService::class);
        $staffingRows = $staffing->buildStaffingRows($event);
        $eventPinataPay = (bool) ($event->pinata_pay ?? false);
        $staffingEventId = (int) $event->id;
        $routePrefix = ((int) (auth()->user()->role_id ?? 0) === 6) ? 'manager' : 'admin';

        return view('admin.event.edit', compact(
            'event',
            'clients',
            'locations',
            'departments',
            'venueTypes',
            'distributors',
            'brands',
            'managers',
            'markets',
            'eventTypes',
            'states',
            'staffingRows',
            'eventPinataPay',
            'staffingEventId',
            'routePrefix'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateEvent(Request $request, ClientBudgetService $budget)
    {
        $event = $request->filled('id') ? Event::find($request->id) : null;
        if ($event && (string) $event->event_status === '1') {
            return $this->updateCompletedEventBilling($request, $event, $budget);
        }

        $request->merge(['booked_by' => $this->normalizeBookedByEmails($request->input('booked_by'))]);

        $validated = $request->validate([
            'event_name' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'event_address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'event_type_id' => 'required|exists:event_types,id',
            'event_location_id' => 'required|exists:event_locations,id',
            'venue_type_id' => 'nullable|exists:venues,id',
            'client_id' => 'required_without:billing_party_ids|nullable|exists:users,id',
            'billing_party_ids' => 'nullable|array',
            'billing_party_ids.*' => 'exists:users,id',
            'primary_billing_party_id' => 'nullable|exists:users,id',
            'department_id' => 'required|exists:event_departments,id',
            'position' => 'required|integer|min:1',
            'distributor_id' => 'required|exists:event_distributors,id',
            'brand_id' => 'required|exists:brands,id',
            'age_restricted' => 'boolean',
            'recap_needed' => 'boolean',
            'market_ids' => 'required|array|min:1',
            'market_ids.*' => 'exists:markets,id',
            'manager_id' => 'required|exists:users,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date',
            'varietals' => 'required|string',
            'notes' => 'required|string',
            'internal_notes' => 'nullable|string',
            'expedite_fee' => 'nullable|string',
            'shipping_fee' => 'nullable|string',
            'parking_fee' => 'nullable|string',
            'drive_time' => 'nullable|string',
            'payment_type' => 'required',
            'booked_by' => ['required', 'array', 'min:1'],
            'booked_by.*' => ['email'],
            'booked_date' => 'required|date',
            'region' => 'required|string|in:Southeast,Midwest',
            'payment_status' => 'boolean',
            'receipt' => 'boolean',
            'kit' => 'boolean',
            'pinata_pay' => 'boolean',
            'integrate_with_best' => 'boolean',
            'best_event_data' => 'nullable|string',
            'is_thc_event' => 'boolean',
            'labor_rates' => 'nullable|array',
            'labor_rates.*' => 'nullable|numeric|min:0',
            'split_cups' => 'boolean',
            'buyback_amount' => 'nullable|numeric|min:0',
            'cups_amount' => 'nullable|numeric|min:0',
        ], [
            'event_type_id.required'     => 'Event Type is required.',
            'event_type_id.exists'       => 'The selected Event Type is invalid.',
            'event_location_id.required'  => 'Venue is required.',
            'event_location_id.exists'    => 'The selected Venue is invalid.',
            'client_id.required_without'  => 'Client (Billing Party) is required.',
            'department_id.required'      => 'Department is required.',
            'position.required'           => 'Positions is required.',
            'position.integer'            => 'Positions must be a whole number.',
            'position.min'                => 'Positions must be at least 1.',
            'distributor_id.required'     => 'Distributor is required.',
            'brand_id.required'           => 'Brand is required.',
            'market_ids.required'         => 'At least one Market is required.',
            'market_ids.min'              => 'At least one Market is required.',
            'manager_id.required'         => 'Manager is required.',
            'start_time.required'         => 'Start Date is required.',
            'start_time.date'             => 'Start Date must be a valid date.',
            'end_time.required'           => 'End Date is required.',
            'end_time.date'               => 'End Date must be a valid date.',
            'varietals.required'          => 'Varietals is required.',
            'notes.required'              => 'Notes is required.',
            'payment_type.required'       => 'Payment Type is required.',
            'booked_by.required'          => 'Booked By is required.',
            'booked_by.*.email'           => 'Each Booked By entry must be a valid email address.',
            'booked_date.required'        => 'Booked Date is required.',
            'booked_date.date'            => 'Booked Date must be a valid date.',
            'region.required'             => 'Region is required.',
            'region.in'                   => 'Region must be Southeast or Midwest.',
        ]);

        $validated['booked_by'] = json_encode($request->input('booked_by'));

        $bestResult = $this->applyBestEventDataToValidated($request, $validated);
        if ($bestResult instanceof \Illuminate\Http\RedirectResponse) {
            return $bestResult;
        }
        $validated = $bestResult;

        $validated['payment_type'] = $this->normalizePaymentType($validated['payment_type'] ?? null);
        $locationService = app(EventLocationService::class);
        $validated = $locationService->applyEventLocationFromVenue($validated);
        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['event_location_id' => 'Selected venue is missing coordinates. Update the venue address first.']);
        }

        $marketIds = collect($validated['market_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();
        $validated['market_id'] = $marketIds[0] ?? null;

        unset($validated['billing_party_ids'], $validated['primary_billing_party_id'], $validated['market_ids'], $validated['labor_rates']);

        $validated['apply_event'] = ($request->apply_event === 'on') ? 1 : 0;
        $validated['pinata_pay'] = ! isset($validated['pinata_pay']) ? 0 : 1;

        $eventType = EventType::find($request->event_type_id);

        if ($eventType && $eventType->name === 'Portal THC Event') {
            $validated['is_thc_event'] = 1;
        }

        $id = $request->id;

        $event ??= Event::findOrFail($id);
        if (! isset($validated['event_name']) || trim((string) $validated['event_name']) === '') {
            $validated['event_name'] = $this->generateEventName($event->id);
        }
        $original = $event->only([
            'start_time',
            'end_time',
            'event_location_id',
            'timezone',
            'event_name',
            'event_type_id',
            'brand_id',
            'distributor_id',
            'department_id',
            'market_id',
            'manager_id',
        ]);
        $staffingService = app(AmbassadorStaffingService::class);
        $staffingRows = $staffingService->decodeStaffingPayload($request->input('ambassador_staffing_data'));
        $removedIds = array_filter(array_map('intval', explode(',', (string) $request->input('removeAmbassadorIds', ''))));
        $eventPinataPay = $request->boolean('pinata_pay');

        $overridePayData = json_decode($request->input('overridePayData'), true);
        $overridePayData = is_array($overridePayData) ? $overridePayData : [];

        $marketIdForBudget = $marketIds[0] ?? ($event->market_id ? (int) $event->market_id : null);
        $managerIdForBudget = (int) ($validated['manager_id'] ?? $event->manager_id ?? 0);
        $extraViolation = $this->managerExtraPayBudgetViolation(
            $managerIdForBudget,
            $marketIdForBudget ? (int) $marketIdForBudget : null,
            (int) $event->id,
            $overridePayData
        );
        if ($extraViolation) {
            return redirect()->back()->withInput()->with('error', $extraViolation);
        }

        if ($request->has('ambassador_staffing_data')) {
            $staffingService->syncStaffingFromForm($event, $staffingRows, $eventPinataPay, $removedIds);
            $event->refresh();
        }
        $validated['ambassador_id'] = $event->ambassador_id;

        // Keep legacy client_id in sync with primary billing party for compatibility.
        $billingPartyIds = $request->input('billing_party_ids', []);
        if (! empty($billingPartyIds)) {
            $primaryId = $request->input('primary_billing_party_id') ?? $billingPartyIds[0];
            $validated['client_id'] = in_array($primaryId, $billingPartyIds) ? $primaryId : $billingPartyIds[0];
        }

        $validated['split_cups'] = $request->boolean('split_cups');
        // Some environments enforce NOT NULL on these columns; default safely to 0.
        $validated['labor_amount'] = $request->filled('labor_amount') ? $request->labor_amount : 0;
        $validated['buyback_amount'] = $request->filled('buyback_amount') ? $request->buyback_amount : 0;
        $validated['cups_amount'] = $request->filled('cups_amount') ? $request->cups_amount : 0;

        $labor = (float) ($validated['labor_amount'] ?? 0);
        $buyback = (float) ($validated['buyback_amount'] ?? 0);
        $cups = (float) ($validated['cups_amount'] ?? 0);
        $primaryClientId = (int) ($validated['client_id'] ?? $event->client_id);
        if ($primaryClientId && (string) $event->event_status !== '2') {
            $billClient = User::find($primaryClientId);
            if ($billClient) {
                $eval = $budget->evaluateTrackedEvent($billClient, $labor, $buyback, $cups, $event->id);
                if ($eval['pending']) {
                    return redirect()->back()->withInput()->with('error', $eval['message'] ?? 'Budget approval is required before saving this over-limit event.');
                }
                if (! $eval['allowed']) {
                    $err = $eval['message'] ?? 'Updated billable amounts would exceed this Bill To\'s budget or allocations.';
                    return redirect()->back()->withInput()->with('error', $err);
                }
            }
        }

        $event->update($validated);
        $event->markets()->sync($marketIds);

        $this->syncEventBillingParties($event, $request);
        $event->labor_amount = $this->calculateLaborAmount($event);
        $event->save();

        if (! empty($request->remove_id)) {
            $attachmentIds = explode(',', $request->remove_id);
            $attachments = Attachement::whereIn('id', $attachmentIds)->get();

            foreach ($attachments as $attachment) {
                $filePath = public_path($attachment->filename);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $attachment->delete();
            }
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $mimeType = $file->getMimeType();
                $destination = public_path('attachments');
                if (! file_exists($destination)) {
                    mkdir($destination, 0755, true);
                }
                $file->move($destination, $filename);
                $pathName = 'attachments/'.$filename;
                $attachment = new Attachement;
                $attachment->event_id = $id;
                $attachment->filename = $pathName;
                $attachment->driver = 1;
                $attachment->type = $mimeType;
                $attachment->save();
            }
        }

        $this->maybeAutoNotifyAmbassadorsOnUpdate($event->fresh(), $original, $validated, $request);

        $routePrefix = ((int) (auth()->user()->role_id ?? 0) === 6) ? 'manager' : 'admin';
        return redirect()->route($routePrefix . '.events')->with('success', 'EVENT ID '.$event->id.' SAVED');
    }

    private function updateCompletedEventBilling(Request $request, Event $event, ClientBudgetService $budget)
    {
        $validated = $request->validate([
            'billing_party_ids' => 'required|array|min:1',
            'billing_party_ids.*' => 'exists:users,id',
            'primary_billing_party_id' => 'nullable|exists:users,id',
            'labor_rates' => 'nullable|array',
            'labor_rates.*' => 'nullable|numeric|min:0',
            'cups_amount' => 'nullable|numeric|min:0',
            'buyback_amount' => 'nullable|numeric|min:0',
            'expedite_fee' => 'nullable|numeric|min:0',
            'shipping_fee' => 'nullable|numeric|min:0',
            'parking_fee' => 'nullable|numeric|min:0',
            'drive_time' => 'nullable|date_format:H:i,H:i:s',
            'payment_type' => 'sometimes|required|in:No purchase,Product on site,Comdata,Check,Reimbursement',
        ], [
            'billing_party_ids.required' => 'At least one Billing Party is required.',
            'billing_party_ids.min' => 'At least one Billing Party is required.',
        ]);

        $billingPartyIds = collect($validated['billing_party_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $primaryId = (int) ($validated['primary_billing_party_id'] ?? $billingPartyIds[0]);
        if (! in_array($primaryId, $billingPartyIds, true)) {
            $primaryId = $billingPartyIds[0];
        }

        $request->merge([
            'billing_party_ids' => $billingPartyIds,
            'primary_billing_party_id' => $primaryId,
        ]);

        DB::beginTransaction();
        try {
            $this->syncEventBillingParties($event, $request);
            $event->client_id = $primaryId;
            $event->split_cups = 0;
            if (array_key_exists('cups_amount', $validated)) {
                $event->cups_amount = $validated['cups_amount'] ?? 0;
            }
            foreach (['buyback_amount', 'payment_type', 'expedite_fee', 'shipping_fee', 'parking_fee', 'drive_time'] as $field) {
                if (array_key_exists($field, $validated)) $event->{$field} = $validated[$field];
            }
            $event->labor_amount = $this->calculateLaborAmount($event);

            $billClient = User::find($primaryId);
            $budgetError = $billClient
                ? $budget->validateBillableChangeForCompletedEvent(
                    $event,
                    $billClient,
                    (float) $event->labor_amount,
                    (float) ($event->buyback_amount ?? 0),
                    (float) $event->cups_amount
                )
                : null;

            if ($budgetError) {
                DB::rollBack();

                return redirect()->back()->withInput()->with('error', $budgetError);
            }

            $event->save();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $routePrefix = ((int) (auth()->user()->role_id ?? 0) === 6) ? 'manager' : 'admin';

        return redirect()->route($routePrefix.'.events')->with('success', 'EVENT ID '.$event->id.' SAVED');
    }

    private function generateEventNameFromNextId(): string
    {
        $lastId = (int) Event::withTrashed()->max('id');

        return $this->generateEventName($lastId + 1);
    }

    private function generateEventName(int $eventId): string
    {
        return 'event-'.$eventId;
    }

    public function notifyBrandAmbassadors(Request $request, Event $event, NotificationDispatchService $dispatch)
    {
        $validated = $request->validate([
            'message_key' => 'required|string|in:event_details_updated,event_time_changed,event_location_changed,custom',
            'custom_message' => 'nullable|string|max:500',
        ]);

        $predefined = [
            'event_details_updated' => 'Event details updated.',
            'event_time_changed' => 'Event time has changed.',
            'event_location_changed' => 'Event location has changed.',
        ];

        $key = $validated['message_key'];
        $message = $key === 'custom'
            ? trim((string) ($validated['custom_message'] ?? ''))
            : $predefined[$key];

        if ($message === '') {
            return redirect()->back()->with('error', 'Please enter a custom message.');
        }

        $sent = $this->dispatchEventUpdateToAmbassadors($event, $dispatch, $message);
        if ($sent === 0) {
            return redirect()->back()->with('error', 'No assigned ambassadors found for this event.');
        }

        return redirect()->back()->with('success', "Notification sent to {$sent} ambassador(s).");
    }

    private function maybeAutoNotifyAmbassadorsOnUpdate(Event $event, array $original, array $validated, Request $request): void
    {
        $auto = $request->boolean('auto_notify_ambassadors');
        if (! $auto) {
            return;
        }

        $changed = [];
        foreach (['start_time', 'end_time', 'event_location_id', 'timezone'] as $field) {
            $new = $validated[$field] ?? null;
            $old = $original[$field] ?? null;
            if ((string) $new !== (string) $old) {
                $changed[] = $field;
            }
        }

        if (empty($changed)) {
            return;
        }

        $messageKey = 'event_details_updated';
        if (array_intersect($changed, ['start_time', 'end_time', 'timezone'])) {
            $messageKey = 'event_time_changed';
        }
        if (in_array('event_location_id', $changed, true) && $messageKey !== 'event_time_changed') {
            $messageKey = 'event_location_changed';
        }

        $messageMap = [
            'event_details_updated' => 'Event details updated.',
            'event_time_changed' => 'Event time has changed.',
            'event_location_changed' => 'Event location has changed.',
        ];

        try {
            $dispatch = app(NotificationDispatchService::class);
            $this->dispatchEventUpdateToAmbassadors($event, $dispatch, $messageMap[$messageKey] ?? 'Event details updated.');
        } catch (\Throwable $e) {
            // Swallow failures: event update must still succeed.
            \Log::warning('Auto-notify ambassadors failed', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function dispatchEventUpdateToAmbassadors(Event $event, NotificationDispatchService $dispatch, string $message): int
    {
        $ambassadorIds = $event->ambassador()->pluck('users.id')->map(fn ($id) => (int) $id)->unique()->values();
        if ($ambassadorIds->isEmpty()) {
            return 0;
        }

        $title = 'Event Update';
        $fullMessage = trim($event->event_name . ': ' . $message);
        $senderId = auth()->id();

        $sent = 0;
        foreach ($ambassadorIds as $receiverId) {
            $dispatch->dispatch(
                $senderId,
                $receiverId,
                'event_update',
                $title,
                $fullMessage,
                null,
                $event->id
            );
            $sent++;
        }

        return $sent;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $routePrefix = ((int) (auth()->user()->role_id ?? 0) === 6) ? 'manager' : 'admin';
        if ($event) {
            $event->delete();

            return redirect()->route($routePrefix . '.events')->with('success', 'Event deleted successfully.');
        }

        return redirect()->route($routePrefix . '.events')->with('error', 'Event Not found');
    }

    // public function searchAmbassador(Request $request) {
    //     $query = User::where('role_id', 5);
    //     $search = $request->search;
    //     $maxAge = $request->age_restricted;

    //     $query->where(function ($q) use ($search) {
    //         $q->where('first_name', 'like', "%$search%")
    //             ->orWhere('last_name', 'like', "%$search%")
    //             ->orWhere('email', 'like', "%$search%");
    //     });

    //     $ambassador = $query->get();
    //     return response()->json([
    //         'status' => 'success',
    //         'data' => $ambassador,
    //     ]);
    // }

    public function searchAmbassador(Request $request)
    {
        $search = $request->search;
        $ageRestricted = filter_var($request->age_restricted, FILTER_VALIDATE_BOOLEAN);

        // Lookup-by-ids: rehydrate previously-selected ambassadors after validation failure.
        $ids = $request->input('ids');
        if (! empty($ids)) {
            if (! is_array($ids)) {
                $ids = array_filter(array_map('trim', explode(',', (string) $ids)));
            }
            $ambassadors = User::with('ambassadorProfile')
                ->where('role_id', 5)
                ->whereIn('id', $ids)
                ->get();
            return response()->json([
                'status' => 'success',
                'data'   => $ambassadors,
            ]);
        }

        $query = User::with('ambassadorProfile')
            ->where('role_id', 5);

        // Filter by name/email
        $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%$search%")
                ->orWhere('last_name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%");
        });

        // Age restriction: dob now lives in ambassadors table
        if ($ageRestricted) {
            $query->whereHas('ambassadorProfile', function ($q) {
                $q->whereNotNull('dob')
                  ->whereDate('dob', '<=', now()->subYears(21)->format('Y-m-d'));
            });
        }

        $ambassadors = $query->get();

        return response()->json([
            'status' => 'success',
            'data'   => $ambassadors,
        ]);
    }

    public function filterEvents(Request $request)
    {
        $query = Event::query();

        // Apply department filter
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Apply market filter
        if ($request->filled('market_id')) {
            $marketId = (int) $request->market_id;
            $query->whereHas('markets', fn ($q) => $q->where('markets.id', $marketId));
        }

        // Apply manager filter
        if ($request->filled('manager_id')) {
            $query->where('manager_id', $request->manager_id);
        }

        // Apply event type filter
        if ($request->filled('venue_id')) {
            $query->where('venue_type_id', $request->venue_id);
        }

        // Apply distributor filter
        if ($request->filled('distributor_id')) {
            $query->where('distributor_id', $request->distributor_id);
        }

        // Apply location filter
        if ($request->filled('location_id')) {
            $query->where('event_location_id', $request->location_id);
        }

        // Apply varietals filter
        if ($request->filled('varietals')) {
            $query->where('varietals', 'like', '%'.$request->varietals.'%');
        }

        // Apply brand_id filter
        if ($request->filled('brand_id')) {
            $query->forBrandOrDescendants($request->brand_id);
        }

        // Apply event_id filter
        if ($request->filled('event_id')) {
            $query->where('id', $request->event_id);
        }

        // Apply payment_type filter
        if ($request->filled('payment_type')) {
            $pt = $request->payment_type;
            $legacy = [
                'No purchase' => ['1'],
                'Product on site' => ['2'],
                'Comdata' => ['3', 'ComData'],
                'Check' => ['4'],
                'Reimbursement' => ['5'],
            ];
            $candidates = array_values(array_unique(array_merge([$pt], $legacy[$pt] ?? [])));
            $query->whereIn('payment_type', $candidates);
        }

        $events = $query->withCount(['pendingAmbassadorApplications as pending_applicants_count'])
            ->paginate(20);

        return response()->json(['html' => view('admin.event.partials.event-table', compact('events'))->render(), 'events' => $events, 'status' => 200]);
    }

    public function updateEventStatus(Request $request, ClientBudgetService $budget)
    {
        $event = Event::findOrFail($request->event_id);

        // Admin/Manager operational status (No Show / cancellation variants)
        if ($request->filled('admin_status') || $request->has('admin_status')) {
            $adminStatus = $request->input('admin_status') ?: null;
            $event->admin_status = $adminStatus;
            $event->admin_status_set_by = auth()->id();
            $event->admin_status_set_at = now();
            $reason = trim((string) $request->input('cancellation_reason', ''));
            $reason = $reason !== '' ? $reason : null;

            // Cancellation logic normalization:
            // - Always clear cups on cancellations to avoid billing cups for cancelled events.
            // - For $15 fee cancellation, enforce flat fee only (labor=15, buyback=0, cups=0).
            // - For short-notice cancellation (full labor), clear cups only.
            if (is_string($adminStatus) && str_starts_with($adminStatus, 'cancelled_')) {
                $event->cancelled_by = auth()->id();
                $event->cancelled_at = now();
                $event->cancellation_reason = $reason;

                $event->split_cups = false;
                $event->cups_amount = 0;

                if ($adminStatus === 'cancelled_2_3_business_days_fee_15') {
                    $event->labor_amount = 15.00;
                    $event->buyback_amount = 0;
                }

                if ($adminStatus === 'cancelled_no_fee') {
                    $event->labor_amount = 0;
                    $event->buyback_amount = 0;
                }

                if ($adminStatus === 'cancelled_24_hours_or_less_full_labor') {
                    // Keep labor/buyback as-is; just ensure cups are removed.
                }
            } else {
                // If ops status is updated away from a cancellation, do not erase
                // cancellation metadata unless BA-facing status is also not cancelled.
                if ((string) ($event->event_status ?? '0') !== '2') {
                    $event->cancelled_by = null;
                    $event->cancelled_at = null;
                    $event->cancellation_reason = null;
                }
            }
            $event->save();

            return response()->json(['success' => true]);
        }

        // BA-facing status (Completed / Pending / Cancelled)
        if ($request->filled('status') || $request->has('status')) {
            $newStatus = (string) $request->status;
            $reason = trim((string) $request->input('cancellation_reason', ''));
            $reason = $reason !== '' ? $reason : null;

            if ($newStatus === '2') {
                $this->clearBudgetApprovalFields($event);
                $event->cancelled_by = auth()->id();
                $event->cancelled_at = now();
                $event->cancellation_reason = $reason;
            }

            if ($newStatus === '0' && (string) $event->event_status === '1') {
                $this->clearBudgetApprovalFields($event);
            }

            if ($newStatus === '1' && (string) $event->event_status !== '1') {
                $client = $event->client_id ? User::find($event->client_id) : null;
                if ($client) {
                    $eval = $budget->evaluateMarkCompleted($event, $client);
                    if ($eval['pending']) {
                        $budget->requestBudgetApproval($event, $client);

                        return response()->json([
                            'success' => true,
                            'budget_pending' => true,
                            'message' => $eval['message'] ?? 'Budget approval requested. Event remains Pending until management approves.',
                        ]);
                    }
                    if (! $eval['allowed']) {
                        return response()->json([
                            'success' => false,
                            'message' => $eval['message'] ?? 'Cannot mark this event Completed due to budget limits.',
                        ], 422);
                    }
                }
            }

            $event->event_status = $newStatus;
            if ($newStatus !== '2') {
                // Clearing cancellation metadata when event is un-cancelled.
                $event->cancelled_by = null;
                $event->cancelled_at = null;
                $event->cancellation_reason = null;
            }
            if ($newStatus === '1') {
                $event->budget_approval_status = null;
                $event->budget_approval_requested_at = null;
                $event->budget_approval_resolved_by = null;
                $event->budget_approval_resolved_at = null;
                $event->budget_approval_note = null;
            }
            $event->save();

            if ($newStatus === '1') {
                $client = $event->client_id ? User::find($event->client_id) : null;
                if ($client) {
                    $budget->notifyThresholdsAfterCompletion($event->fresh(), $client);
                }
            }
        }

        return response()->json(['success' => true]);
    }

    public function approveBudget(Request $request, Event $event)
    {
        if (! in_array((int) auth()->user()->role_id, [1, 6], true)) {
            abort(403);
        }

        if ($event->budget_approval_status !== 'pending') {
            return redirect()->back()->with('error', 'This event is not waiting for budget approval.');
        }

        $event->budget_approval_status = 'approved';
        $event->budget_approval_resolved_by = auth()->id();
        $event->budget_approval_resolved_at = now();
        $event->budget_approval_note = $request->input('budget_approval_note');
        $event->save();

        return redirect()->back()->with('success', 'Budget exception approved. The event can now be marked Completed.');
    }

    public function denyBudget(Request $request, Event $event)
    {
        if (! in_array((int) auth()->user()->role_id, [1, 6], true)) {
            abort(403);
        }

        if ($event->budget_approval_status !== 'pending') {
            return redirect()->back()->with('error', 'This event is not waiting for budget approval.');
        }

        $event->budget_approval_status = 'denied';
        $event->budget_approval_resolved_by = auth()->id();
        $event->budget_approval_resolved_at = now();
        $event->budget_approval_note = $request->input('budget_approval_note');
        $event->save();

        return redirect()->back()->with('success', 'Budget exception denied.');
    }

    protected function clearBudgetApprovalFields(Event $event): void
    {
        $event->budget_approval_status = null;
        $event->budget_approval_requested_at = null;
        $event->budget_approval_resolved_by = null;
        $event->budget_approval_resolved_at = null;
        $event->budget_approval_note = null;
        $event->save();
    }

    public function unApproveAmbassadorEvents()
    {
        $routePrefix = ((int) (auth()->user()->role_id ?? 0) === 6) ? 'manager' : 'admin';
        $staffing = app(AmbassadorStaffingService::class);

        // Pending applications (status 0 / "0" / NULL).
        $assignments = AssignAmbassador::query()
            ->with(['event', 'ambassador'])
            ->where(function ($q) {
                $q->where('status', 0)
                    ->orWhere('status', '0')
                    ->orWhereNull('status');
            })
            ->orderByDesc('id')
            ->get();

        $rows = $assignments->map(function (AssignAmbassador $assignment) use ($staffing, $routePrefix) {
            $ambassador = $assignment->ambassador;
            $event = $assignment->event;
            $ambassadorId = (int) $assignment->ambassador_id;

            return [
                'assignment_id' => (int) $assignment->id,
                'event_id' => (int) $assignment->event_id,
                'event_name' => $event?->event_name ?? '-',
                'event_edit_url' => $event
                    ? route($routePrefix.'.edit.event', $event->id)
                    : '#',
                'event_date' => $event && $event->start_time
                    ? \Carbon\Carbon::parse($event->start_time)->format('m/d/Y g:i A')
                    : '-',
                'name' => $ambassador
                    ? ($ambassador->name ?: trim(($ambassador->first_name ?? '').' '.($ambassador->last_name ?? '')))
                    : '-',
                'email' => $ambassador?->email ?? '-',
                'phone' => $ambassador?->phone ?? '-',
                'profile_image' => $ambassador
                    ? userProfileImageUrl($ambassador->getRawOriginal('profile_image'))
                    : userProfileImageUrl(null),
                'wage' => number_format((float) ($ambassador?->wage ?? 0), 2, '.', ''),
                'overbooked' => $event && $ambassadorId
                    ? $staffing->isOverbooked($ambassadorId, $event)
                    : false,
            ];
        })->values();

        $assignUrl = $routePrefix === 'manager'
            ? route('manager.assign-amb-event')
            : route('ambassador.assign-amb-event');
        $removeUrl = route($routePrefix.'.remove-ambassador-assignment');

        return view('admin.event.unapprove-ambassador', compact(
            'rows',
            'routePrefix',
            'assignUrl',
            'removeUrl'
        ));
    }

    // public function assignAmbEvent(Request $request) {
    //     $id = $request->event_id;
    //     $event = AssignAmbassador::find($id);
    //     $event->status = '1';
    //     $event->save();

    //     $eventData = Event::with('location')->where('id',$event->event_id)->first();
    //     $ambassadordata = User::where('id',$event->ambassador_id)->first();
    //     $data = [
    //         'event_name' => $event->event?$event->event->event_name:'-',
    //         'event_date' => $event->event?$event->event->start_time .' To '. $event->event->end_time : '-',
    //         'location' =>   $eventData->location?$eventData->location->address:'-',
    //         'name' => $ambassadordata->name,
    //     ];
    //     Mail::to('user1@jtplapps.in')->send(new AssignAmbassadorMail($data));
    //     Mail::to($ambassadordata->email)->send(new AssignAmbassadorMail($data));
    //     return response()->json(['status'=>true]);
    // }

    public function assignAmbEvent(Request $request)
    {
        $assignment = AssignAmbassador::with('event')->find($request->event_id);
        if (! $assignment) {
            return response()->json(['message' => 'Assignment not found.'], 404);
        }

        app(AmbassadorStaffingService::class)->approveAssignment($assignment, true);

        return response()->json(['status' => true], 200);
    }

    public function removeAmbassadorAssignment(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|integer',
        ]);

        $assignment = AssignAmbassador::findOrFail($request->assignment_id);
        app(AmbassadorStaffingService::class)->removeAssignment($assignment);

        return response()->json(['status' => true]);
    }

    public function fetchNearByAmbassador(Request $request)
    {
        $venueLatitude  = $request->input('venue_latitude');
        $venueLongitude = $request->input('venue_longitude');

        // Guard: both coordinates must be present and numeric.
        // An empty/null coordinate would cause a PHP TypeError in deg2rad() on PHP 8+.
        if (! is_numeric($venueLatitude) || ! is_numeric($venueLongitude)) {
            return response()->json(['success' => true, 'ambassadors' => []]);
        }

        $venueLatitude  = (float) $venueLatitude;
        $venueLongitude = (float) $venueLongitude;
        $maxDistance    = 10;

        // Guard: if the venue has no coordinates, return empty list gracefully.
        if (! is_numeric($venueLatitude) || ! is_numeric($venueLongitude)) {
            return response()->json([
                'success' => true,
                'ambassadors' => [],
            ]);
        }

        $venueLatitude = (float) $venueLatitude;
        $venueLongitude = (float) $venueLongitude;

        $ambassadors = collect();

        try {
            // Avoid loading all ambassadors into memory; process in chunks.
            // lat/lng now live in the ambassadors table — join to read them.
            User::query()
                ->select(['users.id', 'users.name', 'users.email', 'a.latitude', 'a.longitude'])
                ->join('ambassadors as a', 'a.user_id', '=', 'users.id')
                ->where('users.role_id', 5)
                ->whereNotNull('a.latitude')
                ->whereNotNull('a.longitude')
                ->orderBy('users.id')
                ->chunkById(500, function ($users) use (&$ambassadors, $venueLatitude, $venueLongitude, $maxDistance) {
                    foreach ($users as $user) {
                        // Skip ambassador records with non-numeric coordinates.
                        if (! is_numeric($user->latitude) || ! is_numeric($user->longitude)) {
                            continue;
                        }
                        $distance = $this->calculateDistance(
                            $venueLatitude,
                            $venueLongitude,
                            (float) $user->latitude,
                            (float) $user->longitude
                        );
                        if ($distance <= $maxDistance) {
                            $ambassadors->push($user);
                        }
                    }
                }, 'users.id');
        } catch (\Throwable $e) {
            Log::warning('fetchNearByAmbassador: error during distance calculation', [
                'venue_lat' => $venueLatitude,
                'venue_lng' => $venueLongitude,
                'error'     => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success'     => true,
            'ambassadors' => $ambassadors,
        ]);
    }

    private function calculateDistance($latitude1, $longitude1, $latitude2, $longitude2)
    {
        $earthRadius = 6371;

        $dLat = deg2rad($latitude2 - $latitude1);
        $dLon = deg2rad($longitude2 - $longitude1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c;

        return $distance;
    }

    public function toggleKitDelivered($id)
    {
        $event = Event::findOrFail($id);
        // Status toggle
        $event->kit_delivered = $event->kit_delivered == 1 ? 0 : 1;
        $event->save();

        return response()->json([
            'success' => true,
            'kit_delivered' => $event->kit_delivered,
        ]);
    }

    private function calculateLaborAmount(Event $event): float
    {
        $parties = EventBillingParty::where('event_id', $event->id)->get();
        if ($parties->isEmpty()) {
            return 0.0;
        }

        $start = \Carbon\Carbon::parse($event->start_time);
        $end = \Carbon\Carbon::parse($event->end_time);
        $durationHours = $start->diffInMinutes($end) / 60;

        $total = $parties->count();
        $labor = 0.0;
        foreach ($parties as $party) {
            $rate = (float) ($party->labor_rate ?? 0);
            $labor += ($durationHours * $rate) / $total;
        }

        return round($labor, 2);
    }

    /**
     * Sync event billing parties from request. Keeps client_id in sync with primary.
     */
    private function syncEventBillingParties(Event $event, Request $request): void
    {
        $ids = $request->input('billing_party_ids', []);
        $primaryId = $request->input('primary_billing_party_id');

        if (empty($ids)) {
            EventBillingParty::where('event_id', $event->id)->delete();
            return;
        }

        $primaryId = $primaryId && in_array($primaryId, $ids) ? $primaryId : $ids[0];

        // Labor rates submitted per-party as labor_rates[client_id] => value
        $laborRates = $request->input('labor_rates', []);

        EventBillingParty::where('event_id', $event->id)->delete();

        foreach (array_values($ids) as $sortOrder => $clientId) {
            // Use submitted rate if present; fall back to client profile default
            $laborRate = null;
            if (isset($laborRates[$clientId]) && $laborRates[$clientId] !== '') {
                $laborRate = (float) $laborRates[$clientId];
            } else {
                $client = \App\Models\User::find($clientId);
                if ($client && $client->default_labor_amount !== null) {
                    $laborRate = (float) $client->default_labor_amount;
                }
            }

            EventBillingParty::create([
                'event_id'   => $event->id,
                'client_id'  => $clientId,
                'is_primary' => (int) $clientId === (int) $primaryId,
                'sort_order' => $sortOrder,
                'labor_rate' => $laborRate,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>|\Illuminate\Http\RedirectResponse
     */
    private function applyBestEventDataToValidated(Request $request, array $validated)
    {
        if (empty($validated['integrate_with_best'])) {
            $validated['integrate_with_best'] = 0;

            return $validated;
        }

        if (! empty($validated['best_event_data'])) {
            $bestData = json_decode((string) $validated['best_event_data'], true);
            if (! is_array($bestData)) {
                return redirect()->back()->withInput()->with(
                    'error',
                    'Invalid BEST event configuration. Please open the BEST form and save again.'
                );
            }

            $missing = $this->missingBestRequiredFields($bestData);
            if (! empty($missing)) {
                return redirect()->back()->withInput()->with(
                    'error',
                    'Please fill the following required BEST fields: ' . implode(', ', $missing)
                );
            }

            return $validated;
        }

        $eventId = $request->input('id') ?? $request->input('event_id');
        $existing = $eventId ? Event::find($eventId) : null;

        if ($existing && ! empty($existing->best_event_data)) {
            $validated['best_event_data'] = $existing->best_event_data;

            return $validated;
        }

        return redirect()->back()->withInput()->with(
            'error',
            'BEST Event requires configuration. Please select "BEST Event", complete all required fields in the popup, and click "Save BEST Configuration" before saving the event.'
        );
    }

    /**
     * @return array<string, string>
     */
    private function bestFieldLabels(): array
    {
        return [
            'status' => 'Status',
            'target_audience' => 'Target Audience',
            'account_id' => 'Account',
            'Associated_with_Demand_Creation_Station' => 'Associated with Demand Creation Station',
            'wet_or_dry' => 'Wet or Dry Event',
            'objective' => 'Objective',
            'description' => 'Description',
            'sampling_type' => 'Sampling Type',
            'attendees' => 'Attendees',
            'interactions' => 'Interactions',
            'event_results' => 'Event Results',
            'key_learnings_future_considerations' => 'Key Learnings & Future Considerations',
            'consumer_feedback' => 'Consumer Feedback',
            'account_feedback' => 'Account Feedback',
            'budget' => 'Budget',
            'Account_Premise_Type' => 'Account Premise Type',
            'program_id' => 'Program Name',
            'tag_id' => 'Tag',
            '1l_bottles' => 'Total Bottles Sold 1L',
            '175l_bottles' => 'Total Bottles Sold 1.75L',
            '750ml_bottles' => 'Total Bottles Sold 750ML',
            '375ml_bottles' => 'Total Bottles Sold 375ML',
            'total_samples_distributed' => 'Total Samples Distributed',
            'total_samples_distributed_cocktail' => 'Total Samples Distributed Cocktail',
            'total_samples_distributed_neat_shot' => 'Total Samples Distributed Neat Shot',
            'end_date' => 'End Date',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
        ];
    }

    /**
     * @param  array<string, mixed>  $bestData
     * @return list<string>
     */
    private function missingBestRequiredFields(array $bestData): array
    {
        $labels = $this->bestFieldLabels();
        $missing = [];
        foreach (array_keys($labels) as $key) {
            if (! isset($bestData[$key]) || trim((string) $bestData[$key]) === '') {
                $missing[] = $labels[$key];
            }
        }

        return $missing;
    }

    private function normalizePaymentType(?string $value): ?string
    {
        if ($value === null) return null;

        $map = [
            // Legacy numeric values from older UI
            '1' => 'No purchase',
            '2' => 'Product on site',
            '3' => 'Comdata',
            '4' => 'Check',
            '5' => 'Reimbursement',

            // Legacy/import spellings
            'ComData' => 'Comdata',
            'Comdata' => 'Comdata',
            'comdata' => 'Comdata',
            'No Purchase' => 'No purchase',
            'no purchase' => 'No purchase',
            'Product On Site' => 'Product on site',
            'product on site' => 'Product on site',
        ];

        return $map[$value] ?? $value;
    }

    /**
     * Resolve IANA timezone + label from coordinates (used when an address is selected).
     */
    public function resolveTimezone(Request $request, EventTimezoneService $timezones)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'at' => 'nullable|date',
        ]);

        $resolved = $timezones->resolveFromCoordinates(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            $validated['at'] ?? null
        );

        if ($resolved === null) {
            return response()->json(['message' => 'Unable to determine timezone for this location.'], 422);
        }

        return response()->json($resolved);
    }
}
