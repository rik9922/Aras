<?php

namespace App\Http\Controllers\Api;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Requests\VerifyOTP;
use App\Trait\FormatResponseTrait;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\ForgotPassword;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\RegisterUserRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use App\Services\EventService;
use App\Models\Role;
use App\Models\User;
use App\Models\Event;
use App\Models\EventLocation;
use App\Models\EventDepartment;
use App\Models\EventDistributor;
use App\Models\Brand;
use App\Models\Market;
use App\Models\Venue;
use App\Models\State;
use App\Models\Recap;
use App\Models\AssignAmbassador;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    protected $eventService;
    use FormatResponseTrait;

    public function __construct(EventService $eventService){
        $this->eventService = $eventService;
    }

    public function getEvents(Request $request) {
        $user = JWTAuth::user();
        if($user){
            $this->eventService->markEndedEventsCompletedForApi();

            $lastDayTimestamp = strtotime(date('Y-m-t', strtotime($request->start_date)));
            $events = $this->clientVisibleEventsQuery($user->id)
            ->select(DB::raw('DATE(start_time) as date'),DB::raw('COUNT(*) as count'))
            ->whereBetween('start_time', [
                $request->start_date.' 00:00:00',
                date('Y-m-d', $lastDayTimestamp).' 23:59:59'
            ])
            ->where('event_status', '!=', '2') // Exclude cancelled - badge shows Pending + Completed only
            ->groupBy(DB::raw('DATE(start_time)'))
            ->get();

            if(count($events)>0){
                return $this->successResponse('Event Data.', $events);
            } else {
                 return $this->errorResponse('No Event Data Found for '.date('F',strtotime($request->start_date)), 'ERROR',200,[]);
            }
        }else{
            return $this->errorResponse('User Not Found', [], 403);
        }
    }

    public function getEventlist(Request $request){
        $user = JWTAuth::user();
        if($user){
            $this->eventService->markEndedEventsCompletedForApi();

            $events=[];
            if($request->date){
                $dateQuery = $this->clientVisibleEventsQuery($user->id)->with('eventType')->whereBetween('start_time', [
                    $request->date.' 00:00:00',
                    $request->date.' 23:59:59'
                ])->where('event_status', '!=', '2');
                $total_count=(clone $dateQuery)->count();
                $data = $dateQuery->orderBy('start_time', 'DESC')->offset($request->start)->limit(10)->get();
            } else {
                // Previous requirement (chronological ascending — kept for easy restore):
                // $total_count=Event::with('eventType')->where('client_id',$user->id)->orderByRaw('start_time IS NULL, start_time ASC')->count();
                // $data = Event::with('eventType')->where('client_id',$user->id)->orderByRaw('start_time IS NULL, start_time ASC')->offset($request->start)->limit(10)->get();
                // Latest client/PM requirement — descending by event date (farthest/newest first):
                $listQuery = $this->clientVisibleEventsQuery($user->id)
                    ->with('eventType')
                    ->where('event_status', '0');
                $total_count=(clone $listQuery)->count();
                $data = $listQuery->orderByRaw('start_time IS NULL, start_time DESC, id DESC')->offset($request->start)->limit(10)->get();
            }
            foreach($data as $key=>$event){
                $html='<ion-icon name="eye"  size="large" color="primary" id="'.$event->id.'"></ion-icon>';
                $events[]=array(
                    'id'            => $event->id,
                    'event_name'    => $event->event_name,
                    'booked_date'   => date('M j, Y',strtotime($event->booked_date)),
                    'status'        => $event->event_status=="1" ? "Completed" : ($event->event_status=="2" ? 'Cancelled' : "Pending"),
                    'action'    => $html
                );
            }
            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $events,
                'recordsTotal'      => $total_count,
                'recordsFiltered'   => $total_count
            ]);
        }else{
            return $this->errorResponse('User Not Found', [], 403);
        }
    }


    public function getEventsCalendarData(Request $request) {
        $user = JWTAuth::user();
        if($user){
            $this->eventService->markEndedEventsCompletedForApi();
        }

        $locations = User::select('id', 'name')->where('role_id', 4)->whereNotNull('name')->orderBy('name')->get();
        $markets = Market::get();
        $brands = Brand::get();
        $managers = User::where('role_id', 6)->where('status', '1')->get();
        $ambassadors = User::select('id', 'name', 'email')->where('role_id', 5)->orderBy('name')->get();
        // Previous requirement (chronological ascending — kept for easy restore):
        // $events = Event::with('distributor','department','venueType','brand','location')->orderByRaw('booked_date IS NULL, booked_date DESC, start_time ASC')->get();
        // Latest client/PM requirement — descending by event date (farthest/newest first):
        $events = $user
            ? $this->clientVisibleEventsQuery($user->id)->with('distributor','department','venueType','brand','location')->where('event_status', '0')->orderByRaw('start_time IS NULL, start_time DESC, id DESC')->get()
            : collect();
        $states = EventLocation::orderBy('state')->get();
        $clients = $user ? User::where('id', $user->id)->get() : collect();
        $venueTypes = Venue::get();
        $departments = EventDepartment::get();
        $distributors = EventDistributor::get();

        if($user){
            // Previous requirement (chronological ascending — kept for easy restore):
            // $query = Event::with('distributor','department','venueType','brand','location')->where('client_id',$user->id)->orderByRaw('start_time IS NULL, start_time ASC');
            // Latest client/PM requirement — descending by event date (farthest/newest first):
            $query = $this->clientVisibleEventsQuery($user->id)
                ->with('distributor','department','venueType','brand','location')
                ->where('event_status', '0');
            $recap = [];

            if ($request->filled('market_id')) {
                $marketId = (int) $request->market_id;
                $query->whereHas('markets', fn ($q) => $q->where('markets.id', $marketId));
            }

            if ($request->filled('department_id')) {
                $query->where('department_id', $request->department_id);
            }

            if ($request->filled('venue_id')) {
                $query->where('event_location_id', $request->venue_id);
            }

            if ($request->filled('distributor_id')) {
                $query->where('distributor_id', $request->distributor_id);
            }

            if ($request->filled('varietals')) {
                $query->where('varietals', $request->varietals);
            }

            if ($request->filled('brand_id')) {
                $query->forBrandOrDescendants($request->brand_id);
            }

            if ($request->filled('ambassador_name')) {
                $query->where('ambassador_id', $request->ambassador_name);
            }

            if ($request->filled('ambassador_email')) {
                $query->where('ambassador_id', $request->ambassador_email);
            }

            $total_record=(clone $query)->count();
            $start = max((int) $request->input('start', 0), 0);
            $length = (int) $request->input('length', 25);
            $length = $length > 0 ? min($length, 100) : 25;
            $data=$query->orderByRaw('start_time IS NULL, start_time DESC, id DESC')->offset($start)->limit($length)->get();

            if(count($data)>0){
                foreach($data as $key=>$val){
                    $location = $val->location;
                    $recap[]=array(
                        'id'                 => $val->id,
                        'name'               => $val->event_name,
                        'event_name'         => $val->event_name,
                        'event_status'       => $val->event_status,
                        'booked_date'        => $val->booked_date,
                        'event_date'         => $val->start_time,
                        'start_time'         => $val->start_time,
                        'end_time'           => $val->end_time,
                        'total_hours'        => $val->total_hours,
                        'brand'              => optional($val->brand)->brand_name ?: '-',
                        'brand_name'         => optional($val->brand)->brand_name ?: '-',
                        'venue_name'         => optional($location)->name ?: '-',
                        'location_name'      => optional($location)->name ?: optional($location)->city ?: '-',
                        'event_location'     => optional($location)->name ?: optional($location)->city ?: '-',
                        'event_address'      => $val->event_address ?: optional($location)->address,
                        'action'     => '<ion-icon name="eye"  size="large" color="primary" id="'.$val->id.'"></ion-icon>'
                    );
                }
            }
            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $recap,
                'recordsTotal'      => $total_record,
                'recordsFiltered'   => $total_record,
                'locations'         => $locations,
                'markets'           => $markets,
                'brands'            => $brands,
                'managers'          => $managers,
                'ambassadors'       => $ambassadors,
                'states'            => $states,
                'clients'           => $clients,
                'venueTypes'        => $venueTypes,
                'departments'       => $departments,
                'distributors'      => $distributors,
                'events'            => $events
            ]);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }

    public function clientFilterEvents(Request $request){
        $query = $this->clientVisibleEventsQuery(Auth::id());

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('market_id')) {
            $marketId = (int) $request->market_id;
            $query->whereHas('markets', fn ($q) => $q->where('markets.id', $marketId));
        }

        if ($request->filled('manager_id')) {
            $query->where('manager_id', $request->manager_id);
        }

        if ($request->filled('venue_id')) {
            $query->where('venue_type_id', $request->venue_id);
        }

        if ($request->filled('distributor_id')) {
            $query->where('distributor_id', $request->distributor_id);
        }

        if ($request->filled('location_id')) {
            $query->where('event_location_id', $request->location_id);
        }

        if ($request->filled('varietals')) {
            $query->where('varietals', 'like', '%' . $request->varietals . '%');
        }

        if ($request->filled('brand_id')) {
            $query->forBrandOrDescendants($request->brand_id);
        }

        if ($request->filled('event_id')) {
            $query->where('id', $request->event_id);
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        // Previous requirement (chronological ascending — kept for easy restore):
        // $events = $query->orderBy('start_time','ASC')->get();
        // Latest client/PM requirement — descending by event date (farthest/newest first):
        $events = $query->orderBy('start_time','DESC')->get();
        foreach($events as $key=>$event){
            $events[$key]['start_time']=date("c", strtotime($event['start_time']));
            $events[$key]['end_time']=date("c", strtotime($event['end_time']));
        }
        return $this->successResponse('Events filtered successfully.', [
            'html' => view('client.partials.event-table', compact('events'))->render(),
            'events' => $events,
        ]);
    }

    public function getRecapCalendarData(Request $request){
        $user = JWTAuth::user();

        if(!$user){
            return $this->errorResponse('User Not Found', [], 403);
        }

        $clientEventQuery = $this->clientVisibleEventsQuery($user->id);
        $clientEventIds = (clone $clientEventQuery)->pluck('id');
        $locations = User::select('id', 'name')->where('role_id', 4)->whereNotNull('name')->orderBy('name')->get();
        $markets = Market::get();
        $brands = Brand::get();
        $managers = User::where('role_id', 6)->where('status', '1')->get();
        $ambassadors = User::select('id', 'name', 'email')->where('role_id', 5)->orderBy('name')->get();
        $events = (clone $clientEventQuery)->with('distributor','department','venueType','brand','location')->orderByRaw('start_time IS NULL, start_time DESC, id DESC')->get();
        $states = EventLocation::orderBy('state')->get();
        $clients = User::where('id', $user->id)->get();
        $venueTypes = Venue::get();
        $departments = EventDepartment::get();
        $distributors = EventDistributor::get();

        $baseQuery = Recap::with('event','event.brand','event.location','brand','ambassador')
            ->whereIn('event_id', $clientEventIds);

        $recordsTotal = (clone $baseQuery)->count();
        $query = clone $baseQuery;

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }
        if ($request->filled('ambassador_name')) {
            $query->where('ambassador_id', $request->ambassador_name);
        }
        if ($request->filled('ambassador_email')) {
            $query->where('ambassador_id', $request->ambassador_email);
        }

        if ($request->filled('market_id')) {
            $query->whereHas('event', function($q) use ($request) {
                $marketId = (int) $request->market_id;
                $q->whereHas('markets', fn ($mq) => $mq->where('markets.id', $marketId));
            });
        }

        if ($request->filled('department_id')) {
            $query->whereHas('event', function($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->filled('venue_id')) {
            $query->whereHas('event', function($q) use ($request) {
                $q->where('event_location_id', $request->venue_id);
            });
        }

        if ($request->filled('distributor_id')) {
            $query->whereHas('event', function($q) use ($request) {
                $q->where('distributor_id', $request->distributor_id);
            });
        }

        if ($request->filled('varietals')) {
            $query->whereHas('event', function($q) use ($request) {
                $q->where('varietals', 'like', '%' . $request->varietals . '%');
            });
        }

        if ($request->filled('brand_id')) {
            $query->whereHas('event', function($q) use ($request) {
                $ids = \App\Models\Brand::descendantIdsIncludingSelfById((int) $request->brand_id);
                if (count($ids) <= 1) {
                    $q->where('brand_id', (int) $request->brand_id);
                } else {
                    $q->whereIn('brand_id', $ids);
                }
            });
        }

        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->whereHas('event', function($eventQuery) use ($search) {
                    $eventQuery->where('event_name', 'like', '%' . $search . '%')
                        ->orWhere('id', $search)
                        ->orWhereHas('brand', fn ($brandQuery) => $brandQuery->where('brand_name', 'like', '%' . $search . '%'))
                        ->orWhereHas('location', fn ($locationQuery) => $locationQuery->where('name', 'like', '%' . $search . '%'));
                })
                ->orWhereHas('brand', fn ($brandQuery) => $brandQuery->where('brand_name', 'like', '%' . $search . '%'))
                ->orWhereHas('ambassador', fn ($ambassadorQuery) => $ambassadorQuery->where('name', 'like', '%' . $search . '%')->orWhere('email', 'like', '%' . $search . '%'));
            });
        }

        $recordsFiltered = (clone $query)->count();
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 25);
        $length = $length > 0 ? min($length, 100) : 25;
        $data = $query->orderByDesc('id')->offset($start)->limit($length)->get();

        $recap = $data->map(function($val) {
            $event = $val->event;
            $brandName = optional($val->brand)->brand_name ?: optional(optional($event)->brand)->brand_name;
            $location = optional($event)->location;

            return [
                'id'           => $val->id,
                'recap_id'     => $val->id,
                'event_id'     => optional($event)->id,
                'brand'        => $brandName ?: '-',
                'brand_name'   => $brandName ?: '-',
                'name'         => optional($event)->event_name ?: '-',
                'event_name'   => optional($event)->event_name ?: '-',
                'location'     => optional($location)->name ?: optional($location)->city ?: '-',
                'city'         => optional($location)->city ?: optional($location)->name ?: '-',
                'event_date'   => optional($event)->start_time,
                'submitted_at' => $val->submitted_at ?? $val->updated_at ?? $val->created_at,
                'created_at'   => $val->created_at,
                'ambassador'   => optional($val->ambassador)->name ?: $this->ambassador_Name(optional($event)->id),
                'status'       => $val->status ?? $val->recap_status ?? 'Submitted',
                'action'       => '<ion-icon name="eye"  size="large" color="primary" id="'.optional($event)->id.'"></ion-icon>',
            ];
        })->values();

        return $this->successResponse('Data fetched successfully', [
            "draw"              => (int)$request->draw,
            'data'              => $recap,
            'recordsTotal'      => $recordsTotal,
            'recordsFiltered'   => $recordsFiltered,
            'locations'         => $locations,
            'markets'           => $markets,
            'brands'            => $brands,
            'managers'          => $managers,
            'ambassadors'       => $ambassadors,
            'states'            => $states,
            'clients'           => $clients,
            'venueTypes'        => $venueTypes,
            'departments'       => $departments,
            'distributors'      => $distributors,
            'events'            => $events,
        ]);
    }

    function ambassador_Name($eventId){
        $getAmbassador = AssignAmbassador::where(['event_id' => $eventId, 'status' => '1'])->pluck('ambassador_id')->toArray();
        if($getAmbassador) {
            $ambData = User::whereIn('id',$getAmbassador)->pluck('name')->toArray();
            $name = implode(",",$ambData);
            return $name;
        }

        return '-';
    }

    public function recapFilterEvents(Request $request){
        $submittedId = Recap::pluck('event_id')->toArray();
        $query = Event::whereIn('id',$submittedId);

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
            $query->where('varietals', 'like', '%' . $request->varietals . '%');
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
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->filled('startDate') && $request->filled('endDate')) {
            $startDate = $request->startDate;
            $endDate = $request->endDate;
            $query->whereBetween('start_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        $events = $query->get();

         return $this->successResponse('Recap events filtered successfully.', [
            'events' => $events,
         ]);
    }

    public function getEventDetails(Request $request){
        // $request->validate([
        //     'id' => 'required|integer|exists:events,id',
        // ]);

        $user = auth()->user();
        if (!$user) {
            return $this->errorResponse('Unauthorized', [], 401);
        }

        $this->eventService->markEndedEventsCompletedForApi();

        $event = $this->clientVisibleEventsQuery($user->id)->with(['distributor','department','venueType','brand','location'])
                      ->find($request->id);

        if ($event) {
            $tzFormatted = app(\App\Services\EventTimezoneService::class)->formatEventTimesForApi($event);
            $event->time_display = $tzFormatted['time_display'] ?? null;
            $event->timezone_label = $tzFormatted['timezone_label'] ?? $event->timezone_label;
            $event->formatted_time_with_date = $event->formatted_time_with_date;
            $event->event_location_label = app(\App\Services\EventCheckInService::class)->eventLocationLabel($event);

            return $this->successResponse('Event Data.', $event);
        }

        return $this->errorResponse('Event Not Found', [], 403);
    }

    private function clientVisibleEventsQuery($clientId)
    {
        return Event::where(function($query) use ($clientId) {
            $query->where('client_id', $clientId)
                ->orWhereHas('billingParties', fn ($billingQuery) => $billingQuery->where('client_id', $clientId));
        });
    }


}
