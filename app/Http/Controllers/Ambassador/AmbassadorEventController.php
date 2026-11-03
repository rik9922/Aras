<?php

namespace App\Http\Controllers\Ambassador;

use App\Http\Controllers\Controller;
use App\Mail\ApplyEventMail;
use App\Mail\AmbassadorAppliedEventMail;
use App\Models\AssignAmbassador;
use App\Models\Event;
use App\Models\Market;
use App\Models\User;
use App\Services\EventCheckInService;
use Carbon\Carbon;
use Exception;
use Illuminate\Cache\Events\RetrievingKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Pagination\LengthAwarePaginator;


class AmbassadorEventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     
    public function index(Request $request)
    {
        $dob = Auth::user()->dob;
        $ambassadorId = Auth::id();
        $markets = Market::orderBy('name')->get();
        
        // Calculate ambassador's age
        $ambassadorAge = null;
        if ($dob) {
            $ambassadorAge = Carbon::parse($dob)->age;
        }

        // Get assigned ambassador event IDs for current user (approved)
        $assignAmbasssadorIds = AssignAmbassador::where('ambassador_id', $ambassadorId)
            ->where('status', '1')
            ->pluck('event_id')
            ->toArray();

        // Get pending ambassador event IDs for current user (not yet approved)
        $pendingAmbassadorIds = AssignAmbassador::where('ambassador_id', $ambassadorId)
            ->where('status', '0')
            ->pluck('event_id')
            ->toArray();

        \Log::info($request->get('status', 'all'));
        // Handle AJAX requests
        if ($request->ajax()) {
            $search = $request->get('search');
            $marketId = $request->get('marketId');
            $status = $request->get('status', 'all');
            $page = $request->get('page', 1);
            $perPage = 10; // Items per page

            // Date/time sort direction (mirrors the mobile app's DATE & TIME toggle).
            $sortOrder = $request->get('sort_order', 'asc');
            $sortOrder = in_array($sortOrder, ['asc', 'desc'], true) ? $sortOrder : 'asc';

            // Get all events that match basic filters first.
            //   asc  = upcoming events first, then chronological (nearest first)
            //   desc = most recent / farthest-out first
            $baseQuery = Event::with('distributor', 'department', 'venueType', 'brand', 'location', 'assigneAmbassadors');
            if ($sortOrder === 'desc') {
                $baseQuery->orderBy('start_time', 'desc');
            } else {
                $baseQuery->orderByRaw('CASE WHEN start_time >= NOW() THEN 0 ELSE 1 END ASC')
                    ->orderBy('start_time', 'asc');
            }
                
                
            // Apply age restriction filter
            // If ambassador is under 21 or DOB is null, exclude age-restricted events
            if (empty($ambassadorAge) || $ambassadorAge < 21) {
                $baseQuery->where(function ($q) {
                    $q->where('age_restricted', 0)
                        ->orWhereNull('age_restricted');
                });
            }

            // Apply search filter
            if (!empty($search)) {
                $baseQuery->where(function ($q) use ($search) {
                    $q->whereHas('brand', function ($brandQuery) use ($search) {
                        $brandQuery->where('brand_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('location', function ($locationQuery) use ($search) {
                        $locationQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%");
                    })
                    ->orWhereHas('distributor', function ($distributorQuery) use ($search) {
                        $distributorQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('department', function ($departmentQuery) use ($search) {
                        $departmentQuery->where('name', 'like', "%{$search}%");
                    });
                });
            }

            // Apply market filter
            if (!empty($marketId)) {
                $baseQuery->where('market_id', $marketId);
            }

            // Apply status filter based on your requirements
            if ($status === 'completed') {
                // Show completed events assigned to this ambassador via the
                // assignment table, which is the source used by Assigned too.
                $baseQuery->where('event_status', '1')
                    ->whereIn('id', $assignAmbasssadorIds);
            } elseif ($status === 'pending') {
                // Show only events where ambassador has applied but not approved yet
                $baseQuery->whereIn('id', $pendingAmbassadorIds);
            } elseif ($status === 'assigned') {
                // Completed assignments belong exclusively in the Completed tab.
                $baseQuery->whereIn('id', $assignAmbasssadorIds)
                    ->where('event_status', '!=', '1');
            }
            // For 'all' status, we don't add any additional filters

            // Get all matching events
            $allEvents = $baseQuery->get();

            // Filter events based on ambassador assignment logic
            // Only apply this filter for 'all' status, as other statuses are already filtered above
            if ($status === 'all') {
                $filteredEvents = $allEvents->filter(function ($event) use ($assignAmbasssadorIds) {
                    $assignedIds = ! empty($event->ambassador_id) ? explode(',', $event->ambassador_id) : [];
                    $assignedCount = count($assignedIds);
                    $position = (int) $event->position;
                    $isAssigned = in_array($event->id, $assignAmbasssadorIds);

                    // If event is completed, only show if current user is assigned to it
                    if ($event->event_status == '1') {
                        return $isAssigned;
                    }

                    // Show event if it's not full or current user is already assigned
                    return $assignedCount < $position || $isAssigned;
                });
            } else {
                // For specific status filters, use all events from query
                $filteredEvents = $allEvents;
            }

            // Manual pagination
            $totalCount = $filteredEvents->count();
            $offset = ($page - 1) * $perPage;
            $paginatedEvents = $filteredEvents->slice($offset, $perPage);

            // Create pagination object
            $paginator = new LengthAwarePaginator(
                $paginatedEvents,
                $totalCount,
                $perPage,
                $page,
                [
                    'path' => request()->url(),
                    'pageName' => 'page',
                ]
            );

            // Generate HTML for table rows
            $html = '';
            foreach ($paginatedEvents as $event) {
                $html .= view('ambassador.event.event_row', compact('event', 'status', 'ambassadorId', 'assignAmbasssadorIds', 'pendingAmbassadorIds'))->render();
            }

            return response()->json([
                'html' => $html,
                'count' => $totalCount,
                'pagination' => $paginator->links('ambassador.event.pagination')->render(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage()
            ]);
        }

        // For non-AJAX requests, just return the view with markets
        return view('ambassador.event.index', compact('markets'));
    }
     
    // public function index(Request $request)
    // {
    //     $ambassadorId = Auth::id();
    //     $getAll = AssignAmbassador::where('ambassador_id', Auth::id())->pluck('event_id')->toArray();
    //     $assignAmbasssadorIds = AssignAmbassador::where('ambassador_id', Auth::id())->where('status', '1')->pluck('event_id')->toArray();
    //     $pendingAmbassadorIds = AssignAmbassador::where('ambassador_id', Auth::id())->where('status', '0')->pluck('event_id')->toArray();
    //     $completedEvent = Event::where('event_status', '1')->pluck('id')->toArray();

        
    //     if ($request->ajax()) {
    //         $search = $request->search;
    //         $marketId = $request->marketId;
    //         $status = $request->status;
        
    //         $events = Event::withCount('assigneAmbassador') // <- Add this
    //             ->with('distributor', 'department', 'venueType', 'brand', 'location')
    //             ->when($marketId, function ($query) use ($marketId) {
    //                 return $query->where('market_id', $marketId);
    //             })
    //             ->when($search, function ($query) use ($search) {
    //                 return $query->whereHas('brand', function ($q) use ($search) {
    //                     return $q->where('brand_name', 'LIKE', "%$search%");
    //                 });
    //             })
    //             ->when($status === 'Pending', function ($query) use ($pendingAmbassadorIds) {
    //                 return $query->whereIn('id', $pendingAmbassadorIds);
    //             })
    //             ->when($status === 'Assigned', function ($query) use ($assignAmbasssadorIds) {
    //                 return $query->whereIn('id', $assignAmbasssadorIds);
    //             })
    //             ->when($status === 'Completed', function ($query) use ($completedEvent) {
    //                 return $query->whereIn('id', $completedEvent);
    //             })
    //             ->orderBy('id', 'desc')
    //             ->get()
    //             ->filter(function ($event) use ($ambassadorId) {
    //                 $assignedIds = array_map('intval', explode(',', $event->ambassador_id ?? '')); // handle null
    //                 $assignedCount = $event->assigne_ambassador_count;
    //                 $position = (int) $event->position;
        
    //                 // Show if not full or the current user is already assigned
    //                 return $assignedCount < $position || in_array($ambassadorId, $assignedIds);
    //             })
    //             ->values(); // reindex
        
    //         return response()->json([
    //             'html' => view('ambassador.event.event_table', compact('events', 'assignAmbasssadorIds', 'pendingAmbassadorIds', 'status'))->render()
    //         ]);
    //     }


    //     $markets = Market::get();
        
    //     $events = Event::withCount('assigneAmbassador')
    //         ->with('distributor', 'department', 'venueType', 'brand', 'location', 'assigneAmbassadors')
    //         ->whereIn('id', $getAll)
    //         ->orderBy('id', 'DESC')
    //         ->get()
    //         ->filter(function ($event) use ($ambassadorId) {
    //             $assignedIds = explode(',', $event->ambassador_id); // convert string to array
    //             $assignedCount = $event->assigne_ambassador_count;
    //             $position = (int) $event->position;
        
    //             // show event if it's not full or current user is already assigned
    //             return $assignedCount < $position || in_array($ambassadorId, $assignedIds);
    //         })
    //         ->values();
            
    //     // return response()->json($events);

    //     return view('ambassador.event.index', compact('markets', 'events', 'assignAmbasssadorIds', 'pendingAmbassadorIds'));
    // }

    


     public function applyEvent(Request $request) {
        try {
            $request->validate([
                'event_id' => 'required|integer|exists:events,id',
            ]);

            // Prevent duplicate applications for the same event.
            $existing = AssignAmbassador::query()
                ->where('event_id', (int) $request->event_id)
                ->where('ambassador_id', (int) Auth::id())
                ->first();
            if ($existing) {
                return response()->json(['status' => true, 'already_applied' => true]);
            }

            $eventAssign = new AssignAmbassador();
            $eventAssign->event_id = $request->event_id;
            $eventAssign->ambassador_id = Auth::id();
            $eventAssign->status = '0';
            $eventAssign->save();

            $data = [
                'event_name' => $eventAssign->event?$eventAssign->event->event_name:'-',
                'event_date' => $eventAssign->event?$eventAssign->event->start_time .' '.$eventAssign->event->end_time:'-' ,
                'name' => Auth::user()->name,
                'email' => Auth::user()->email,
                'phone' => Auth::user()->phone,
            ];
            Mail::to('user1@jtplapps.in')->send(new ApplyEventMail($data));
            Mail::to('user1@jtplapps.in')->send(new AmbassadorAppliedEventMail($data));
            Mail::to(Auth::user()->email)->send(new AmbassadorAppliedEventMail($data));
            return response()->json(['status'=>true]);
        } catch(Exception $e) {
            Log::info("Sending Mail Issue =>".$e->getMessage());
        }
    }

    public function withdrawEvent(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|integer|exists:events,id',
        ]);

        $ambassadorId = (int) Auth::id();
        $eventId = (int) $validated['event_id'];

        $event = Event::query()->select(['id', 'event_status', 'ambassador_id'])->findOrFail($eventId);

        if ((string) ($event->event_status ?? '') === '1') {
            return response()->json([
                'status' => false,
                'message' => 'You cannot withdraw from a completed event.',
            ], 422);
        }

        $assignment = AssignAmbassador::query()
            ->where('event_id', $eventId)
            ->where('ambassador_id', $ambassadorId)
            ->first();

        if (! $assignment) {
            // Idempotent: treat as already withdrawn
            return response()->json(['status' => true, 'already_withdrawn' => true]);
        }

        // Withdrawal is only available while the application is pending. An
        // approved assignment must be removed by an admin/manager instead.
        if ((string) ($assignment->status ?? '') !== '0') {
            return response()->json([
                'status' => false,
                'message' => 'You can only withdraw a pending event application.',
            ], 422);
        }

        $assignment->delete();

        return response()->json(['status' => true]);
    }

    public function checkInEvent(Request $request, EventCheckInService $checkInService)
    {
        $validated = $request->validate([
            'event_id' => 'required|integer|exists:events,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'force_check_in' => 'sometimes|boolean',
        ]);

        $ambassadorId = (int) Auth::id();
        $eventId = (int) $validated['event_id'];

        $assignment = AssignAmbassador::query()
            ->where('event_id', $eventId)
            ->where('ambassador_id', $ambassadorId)
            ->first();

        if (! $assignment || (string) ($assignment->status ?? '') !== '1') {
            return response()->json([
                'status' => false,
                'message' => 'You can only check in to an assigned event.',
            ], 422);
        }

        $event = Event::with(['location', 'brand', 'eventType', 'department', 'distributor'])->findOrFail($eventId);
        $result = $checkInService->processCheckIn(
            Auth::user(),
            $event,
            $assignment,
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            (bool) ($validated['force_check_in'] ?? false),
            null
        );

        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'status' => false,
                'message' => $result['message'] ?? 'Check-in could not be completed.',
                'requires_confirmation' => (bool) ($result['requires_confirmation'] ?? false),
                'distance_meters' => $result['distance_meters'] ?? null,
                'allowed_radius_meters' => $result['allowed_radius_meters'] ?? null,
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Check-in recorded successfully.',
        ]);
    }
}
