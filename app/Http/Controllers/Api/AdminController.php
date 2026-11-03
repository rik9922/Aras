<?php

namespace App\Http\Controllers\Api;

use DB;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helper\QuickBooksHelper;
use App\Http\Requests\VerifyOTP;
use App\Trait\FormatResponseTrait;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Concerns\NormalizesBookedByEmails;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\ForgotPassword;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\RegisterUserRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use App\Exports\AdminRecapEvent;
use App\Services\EventService;
use App\Services\QuickBooksService;
use App\Services\RecapExportService;
use App\Models\Role;
use App\Models\User;
use App\Models\Client;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\EventLocation;
use App\Models\Venue;
use App\Models\EventDepartment;
use App\Models\EventDistributor;
use App\Models\Brand;
use App\Models\Market;
use App\Models\EventType;
use App\Models\EventBillingParty;
use App\Models\BrandCategory;
use App\Models\Recap;
use App\Models\State;
use App\Models\Setting;
use App\Models\Service;
use App\Models\PaymentMethod;
use App\Models\QuickBooksTerm;
use App\Models\QuickBooksAccount;
use App\Models\ExpenseCategory;
use App\Models\AssignAmbassador;
use App\Models\Attachement;
use App\Models\ArasDocuments;
use App\Models\AmbassadorPayout;
use App\Models\PayrollBatchItem;
use App\Models\Reimbursement;
use App\Jobs\CreateQuickBooksCustomer;
use App\Jobs\SyncAllClientsWithQuickBooks;
use App\Jobs\ProcessPayrollBatch;
use App\Services\EventLocationService;
use App\Services\EventTimezoneService;
use App\Services\EventCheckInService;
use App\Services\AmbassadorStaffingService;
use App\Services\NotificationDispatchService;
use App\Services\PayrollBatchService;
use Illuminate\Support\Facades\Log;
use QuickBooksOnline\API\DataService\DataService;

class AdminController extends Controller
{
    use NormalizesBookedByEmails;

    protected $eventService;
    use FormatResponseTrait;
    
    public function __construct(EventService $eventService){
        $this->eventService = $eventService;
    }

    /**
     * For POS users (role_id 7): assigned market IDs. Null means not a POS user (no restriction).
     *
     * @return list<int>|null
     */
    protected function posUserAllowedMarketIds(?User $user): ?array
    {
        if (! $user || (int) $user->role_id !== 7) {
            return null;
        }

        return $user->posMarkets()->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    protected function applyPosMarketScopeToEvents($query, ?User $user): void
    {
        $ids = $this->posUserAllowedMarketIds($user);
        if ($ids === null) {
            return;
        }
        if ($ids === []) {
            $query->whereRaw('0 = 1');

            return;
        }
        $query->whereHas('markets', function ($q) use ($ids) {
            $q->whereIn('markets.id', $ids);
        });
    }

    /**
     * Match web admin event index filter dropdowns (EventController::index).
     */
    protected function ambassadorsForEventFilters()
    {
        return User::query()
            ->select(['id', 'name', 'email', 'first_name', 'last_name'])
            ->where('role_id', 5)
            ->orderBy('name')
            ->limit(1000)
            ->get();
    }

    /**
     * Event ID filter options — same ordering/status rules as web admin events page.
     */
    protected function eventsForEventFilters(?User $user, Request $request)
    {
        $query = Event::query()
            ->select(['id', 'event_name'])
            // Previous requirement (chronological ascending — kept for easy restore):
            // ->orderByRaw('booked_date IS NULL, booked_date DESC, start_time ASC')
            // Latest client/PM requirement — descending by event date (farthest/newest first):
            ->orderByRaw('start_time IS NULL, start_time DESC, id DESC')
            ->limit(2000);

        $this->applyPosMarketScopeToEvents($query, $user);

        if ($request->filled('status')) {
            $query->where('event_status', $request->input('status'));
        } elseif ($request->boolean('show_completed')) {
            $query->where('event_status', '1');
        }

        return $query->get();
    }

    /**
     * Plain arrays for mobile/web filter dropdowns (stable JSON shape).
     */
    protected function formatFilterAmbassadors($ambassadors): array
    {
        return collect($ambassadors)->map(function ($user) {
            $displayName = trim((string) ($user->name ?? ''));
            if ($displayName === '') {
                $displayName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
            }
            $email = (string) ($user->email ?? '');

            return [
                'id' => $user->id,
                'name' => $displayName,
                'email' => $email,
                'label' => $displayName !== '' ? $displayName : $email,
            ];
        })->values()->all();
    }

    protected function formatFilterEvents($events): array
    {
        return collect($events)->map(function ($event) {
            $name = (string) ($event->event_name ?? '');
            $id = $event->id;

            return [
                'id' => $id,
                'event_name' => $name,
                'label' => $name !== '' ? "{$name} ({$id})" : (string) $id,
            ];
        })->values()->all();
    }

    /**
     * Filter metadata shared by get_events_data and get_event_list responses.
     */
    protected function eventFilterMetadata(?User $user, Request $request): array
    {
        $filterAmbassadors = $this->formatFilterAmbassadors($this->ambassadorsForEventFilters());
        $filterEvents = $this->formatFilterEvents($this->eventsForEventFilters($user, $request));

        return [
            'ambassadors' => $filterAmbassadors,
            'eventsData' => $filterEvents,
            'events' => $filterEvents,
            'filter_ambassadors' => $filterAmbassadors,
            'filter_events' => $filterEvents,
        ];
    }

    protected function marketsForUser(?User $user)
    {
        if ($user && (int) $user->role_id === 6 && (string) $user->status !== '1') {
            return collect();
        }

        if ($user && (int) $user->role_id === 7) {
            return $user->posMarkets()->orderBy('name')->get();
        }

        return Market::query()->active()->ordered()->get();
    }

    protected function applyPosMarketScopeToRecapQuery($query, ?User $user): void
    {
        $ids = $this->posUserAllowedMarketIds($user);
        if ($ids === null) {
            return;
        }
        if ($ids === []) {
            $query->whereRaw('0 = 1');

            return;
        }
        $query->whereHas('event', function ($q) use ($ids) {
            $q->whereHas('markets', function ($mq) use ($ids) {
                $mq->whereIn('markets.id', $ids);
            });
        });
    }
    
    public function getEvents(Request $request){
        $user = JWTAuth::user();
        if($user){
            $this->eventService->markEndedEventsCompletedForApi();

            $lastDayTimestamp = strtotime(date('Y-m-t', strtotime($request->start_date)));
            
            $eventsQuery = Event::select(DB::raw('DATE(start_time) as date'),DB::raw('COUNT(*) as count'))
            ->whereBetween('start_time', [
                $request->start_date.' 00:00:00',
                date('Y-m-d', $lastDayTimestamp).' 23:59:59'
            ])
            ->where('event_status', '0'); // Calendar badge counts include Pending events only
            $this->applyPosMarketScopeToEvents($eventsQuery, $user);
            $events = $eventsQuery->groupBy(DB::raw('DATE(start_time)'))->get();
                
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
        }

        $includeFilters = $request->boolean('include_filters', true);
        $locations = [];
        $markets = [];
        $brands = [];
        $managers = [];
        $states = [];
        $clients = [];
        $venueTypes = [];
        $departments = [];
        $distributors = [];
        $eventTypes = [];
        $filterMetadata = $includeFilters
            ? $this->eventFilterMetadata($user, $request)
            : [];

        if ($includeFilters) {
            $locations = EventLocation::select('id', 'name', 'address', 'city', 'state')
                ->orderBy('name')
                ->orderBy('address')
                ->get();
            $markets = $this->marketsForUser($user);
            $brands = Brand::get();
            $managers = User::where('role_id', 6)->where('status', '1')->get();
            $states = EventLocation::orderBy('state')->get();
            $clients = User::where('role_id', 3)->get();
            $venueTypes = Venue::get();
            $departments = EventDepartment::get();
            $distributors = EventDistributor::get();
            $eventTypes = EventType::get();
        }

        $events = Event::with('brand', 'location', 'eventType', 'client', 'recap','assignAmbassadors','assignAmbassadors.ambassador', 'markets')
                    ->where('event_status', '1')
                    // Previous requirement (chronological ascending — kept for easy restore):
                    // ->orderByRaw('booked_date IS NULL, booked_date DESC, start_time ASC');
                    // Latest client/PM requirement — descending by event date (farthest/newest first):
                    ->orderByRaw('start_time IS NULL, start_time DESC, id DESC');
                    
        if($user){
            $events=[];
            if($request->date){
                // Previous requirement (chronological ascending — kept for easy restore):
                // ->orderBy('start_time', 'ASC')
                // Latest client/PM requirement — descending by event date (farthest/newest first):
                $data = Event::with('eventType', 'markets', 'location')->whereBetween('start_time', [
                    $request->date.' 00:00:00',
                    $request->date.' 23:59:59'
                ])->where('event_status', '!=', '2')->orderBy('start_time', 'DESC')->offset($request->start)->limit((int)($request->length ?? 20));
                
                $total_count=Event::with('eventType', 'markets')->whereBetween('start_time', [
                    $request->date.' 00:00:00',
                    $request->date.' 23:59:59'
                ])->where('event_status', '!=', '2')->orderBy('start_time', 'DESC');
            } else {
                // Previous requirement (chronological ascending — kept for easy restore):
                // $total_count=Event::with('eventType', 'markets')->orderByRaw('start_time IS NULL, start_time ASC');
                // $data = Event::with('eventType', 'markets')->orderByRaw('start_time IS NULL, start_time ASC')->offset($request->start)->limit(10);
                // Latest client/PM requirement — descending by event date (farthest/newest first):
                $total_count=Event::with('eventType', 'markets')->orderByRaw('start_time IS NULL, start_time DESC, id DESC');
                $data = Event::with('eventType', 'markets', 'location')->orderByRaw('start_time IS NULL, start_time DESC, id DESC')->offset($request->start)->limit((int)($request->length ?? 20));
            }
            
            if ($request->filled('event_status')) {
                // Explicit event_status param: honour it exactly.
                $data->where('event_status', $request->event_status);
                $total_count->where('event_status', $request->event_status);
            } elseif ($request->filled('status') && $request->status == 0 && ! $request->filled('event_id')) {
                // Explicit status=0 param: show pending events only.
                // NOTE: the original `$request->status == 0` check (without
                // filled()) was a PHP loose-comparison trap — null == 0 is true,
                // so every request that omitted status (including all calendar
                // date-wise requests) fell into this branch and incorrectly
                // applied a status filter.
                $data->where('event_status', '0');
                $total_count->where('event_status', '0');
            } elseif (!$request->filled('date') && ! $request->filled('event_id')) {
                // No date context (general event-list page) and no explicit
                // status filter: apply the default pending-only scope.
                $data->where('event_status', '0');
                $total_count->where('event_status', '0');
            }
            // When called with ?date=... (calendar date-wise view), cancelled
            // events (event_status = '2') are excluded so the list matches the
            // badge count from getEvents(), which also excludes cancelled events.
            
            if ($request->filled('department_id')) {
                $data->where('department_id', $request->department_id);
            }
            if ($request->filled('market_id')) {
                $marketId = (int) $request->market_id;
                $data->whereHas('markets', fn ($q) => $q->where('markets.id', $marketId));
            }
            if ($request->filled('manager_id')) {
                $data->where('manager_id', $request->manager_id);
            }
            if ($request->filled('event_type_id')) {
                $data->where('event_type_id', $request->event_type_id);
            }
            if ($request->filled('venue_id')) {
                $data->where('event_location_id', $request->venue_id);
            }
            if ($request->filled('location')) {
                $data->where('event_location_id', $request->location);
            }
            if ($request->filled('distributor_id')) {
                $data->where('distributor_id', $request->distributor_id);
            }
            if ($request->filled('event_id')) {
                $data->where('id', $request->event_id);
            }
            if ($request->filled('state_id')) {
                $data->where('state_id', $request->state_id);
            }
            if ($request->filled('location_state')) {
                $userLocation = User::where('event_location_id', $request->location_state)->first();
                if ($userLocation) {
                    $data->where('event_location_id', $userLocation->id);
                } else {
                    $data->whereRaw('0 = 1');
                }
            }
            if ($request->filled('varietals')) {
                $data->where('varietals', 'like', '%'.$request->varietals.'%');
            }
            if ($request->filled('client_id')) {
                $data->where('client_id', $request->client_id);
            }
            if ($request->filled('bill_id')) {
                $data->where('client_id', $request->bill_id);
            }
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
                $data->whereIn('payment_type', $candidates);
            }
            if ($request->filled('billable_name')) {
                $data->where('event_name', 'like', '%'.$request->billable_name.'%');
            }
            if ($request->filled('range')) {
                $data->where('range', $request->range);
            }
            if ($request->filled('cpr_expiry')) {
                $data->where('cpr_expiry', $request->cpr_expiry);
            }
            if ($request->filled('ambassador_email')) {
                $data->whereRaw("FIND_IN_SET(?, ambassador_id)", [$request->ambassador_email]);
            }
            if ($request->filled('brand_id')) {
                $data->forBrandOrDescendants($request->brand_id);
            }
            if ($request->filled('ambassador_name')) {
                $data->whereRaw("FIND_IN_SET(?, ambassador_id)", [$request->ambassador_name]);
            }
            if ($request->filled('region')) {
                $data->where('region', $request->region);
            }
            // include_nearby_events: not implemented — events table has no is_nearby / geo filter column.
            if ($request->boolean('overview_needed')) {
                $data->where('recap_needed', 1);
                $total_count->where('recap_needed', 1);
            }
            if ($request->boolean('not_fully_staffed')) {
                $data->whereRaw(
                    '(SELECT COUNT(*) FROM assign_ambassadors WHERE assign_ambassadors.event_id = events.id) < COALESCE(NULLIF(events.position, 0), 1)'
                );
                $total_count->whereRaw(
                    '(SELECT COUNT(*) FROM assign_ambassadors WHERE assign_ambassadors.event_id = events.id) < COALESCE(NULLIF(events.position, 0), 1)'
                );
            }
            if ($request->boolean('exclude_cancellations')) {
                $data->where('event_status', '!=', '2');
                $total_count->where('event_status', '!=', '2');
            }
            if ($request->boolean('kit_required')) {
                $data->where('kit', 1);
                $total_count->where('kit', 1);
            }
            if ($request->boolean('backup_brand_used')) {
                $data->whereHas('recap', function ($q) {
                    $q->where('backup_brand_used', 1);
                });
            }
            if ($request->filled('backup_brand_name')) {
                $data->whereHas('recap', function ($q) use ($request) {
                    $q->where('backup_brand_name', 'like', '%'.$request->backup_brand_name.'%');
                });
            }
            if ($request->filled('best_sync')) {
                $best = (string) $request->best_sync;
                if ($best === 'synced') {
                    $data->whereNotNull('best_synced_at');
                } elseif ($best === 'not_synced') {
                    $data->whereNull('best_synced_at');
                } elseif ($best === 'failed') {
                    $data->where('best_status', 'failed');
                } elseif ($best === 'pending') {
                    $data->where('best_status', 'pending')->whereNull('best_synced_at');
                }
            }

            $this->applyPosMarketScopeToEvents($data, $user);
            $this->applyPosMarketScopeToEvents($total_count, $user);
            
            if ($request->filled('startDate') && $request->filled('endDate')) {
                $startDate = $request->startDate;
                $endDate = $request->endDate;
                $data->where(function($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                      ->orWhereBetween('end_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                      ->orWhere(function($subQ) use ($startDate, $endDate) {
                          $subQ->where('start_time', '<=', $startDate . ' 00:00:00')
                                ->where('end_time', '>=', $endDate . ' 23:59:59');
                      });
                });
            }
            
            $data = $data->get();
            $total_count = $total_count->count();
            
            foreach($data as $key=>$event){
                if (in_array((int) $user->role_id, [1, 6], true)) {
                    $html='<ion-icon name="create" size="large" color="success" id="'.$event->id.'"></ion-icon>';
                } else {
                    $html='<ion-icon name="eye" size="large" color="primary" id="'.$event->id.'"></ion-icon>';
                }
                // Compute total hours from start_time and end_time
                $totalHours = '';
                if ($event->start_time && $event->end_time) {
                    try {
                        $start = new \DateTime($event->start_time);
                        $end   = new \DateTime($event->end_time);
                        if ($end < $start) { $end->modify('+1 day'); }
                        $diff = $start->diff($end);
                        $totalHours = sprintf('%02d:%02d Hrs', $diff->h + ($diff->days * 24), $diff->i);
                    } catch (\Exception $e) { $totalHours = ''; }
                }

                $events[]=array(
                    'id'            => $event->id,
                    'event_name'    => $event->event_name,
                    'booked_date'   => $event->booked_date ? date('m/d/Y', strtotime($event->booked_date)) : '',
                    'start_time'    => $event->start_time ? date('h:i A', strtotime($event->start_time)) : '',
                    'end_time'      => $event->end_time   ? date('h:i A', strtotime($event->end_time))   : '',
                    'timezone_label'=> $event->timezone_label ?? '',
                    'total_hours'   => $totalHours,
                    'brand_name'    => $event->brand->brand_name ?? '',
                    'location_name' => trim((string) ($event->location->name ?? '')),
                    'location_address' => trim(
                        ($event->location->address ?? $event->event_address ?? '')
                        . (! empty($event->location->city) ? ', ' . $event->location->city : '')
                    ),
                    'event_address' => trim(($event->event_address ?? '') . ($event->city ? ', ' . $event->city : '')),
                    'event_status'  => $event->event_status,
                    'status'        => $event->event_status=="1" ? '<div class="badge-success">Completed</div>' : ($event->event_status=="2" ? '<div class="badge-danger">Cancelled</div>' : '<div class="badge-warning">Pending</div>'),
                    'action'        => $html,
                );
            }
            return $this->successResponse('Data fetched successfully', [
                "draw"              =>   (int)$request->draw,
                'data'              =>   $events,
                'recordsTotal'      =>   $total_count,
                'recordsFiltered'   =>   $total_count,
                'getEventHours'     =>   $this->eventService->getTotalEventHour(),
                'locations'         =>   $locations,
                'markets'           =>   $markets,
                'brands'            =>   $brands,
                'managers'          =>   $managers,
                'states'            =>   $states,
                'clients'           =>   $clients,
                'venueTypes'        =>   $venueTypes,
                'departments'       =>   $departments,
                'distributors'      =>   $distributors,
                'eventTypes'        =>   $eventTypes,
                'table_events'      =>   $events,
            ] + $filterMetadata);
        }else{
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function getEventTypes(Request $request){
        $user = JWTAuth::user();
        if($user){
            $data = EventType::orderBy('id','DESC')->get();
            return $this->successResponse('Event Data List', $data);
        }else{
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function saveEventType(Request $request){
        $user = JWTAuth::user();
        if($user){
            $Validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'string',
                    Rule::unique('event_types', 'name')->ignore($request->id),
                ],
                'description' => 'required|string|max:255',
                'recap_type' => ['nullable', Rule::in(\App\Services\RecapFormService::eventTypeRecapTypes())],
            ]);
            if ($Validator->fails()) {
                $errors = $Validator->errors();
                foreach ($errors->all() as $key => $value) {
                    return $this->errorResponse(ucfirst($value), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
                }
            }
            
            if(isset($request->id) && !empty($request->id)){
                $temp=EventType::find($request->id);
                $temp->name=$request->name;
                $temp->description=$request->description;
                $temp->recap_type=$request->recap_type ?: null;
                $temp->save();
                return $this->successResponse('Event Type Data Updated', []);
            } else {
                EventType::create($request->only(['name', 'description', 'recap_type']));
                return $this->successResponse('Event Type Data Saved', []);
            }
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }

    public function deleteEventType(Request $request){
        $user = JWTAuth::user();
        if($user){
            $Validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:event_types,id',
            ]);
            if ($Validator->fails()) {
                $errors = $Validator->errors();
                foreach ($errors->all() as $key => $value) {
                    return $this->errorResponse(ucfirst($value), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
                }
            }

            $eventType = EventType::find($request->id);
            $linkedEvents = Event::where('event_type_id', $eventType->id)->exists();

            if ($linkedEvents) {
                return $this->errorResponse('This event type is linked with an event and cannot be deleted.', 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
            }

            $eventType->delete();

            return $this->successResponse('Event Type Data Deleted', []);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function getClients(Request $request){
        $user = JWTAuth::user();
        if($user){
            $query = User::query()
                ->where('role_id', 3)
                ->with(['clientProfile.parentBillTo:id,first_name,last_name,name']);

            $totalBillables = (clone $query)->count();

            if($request->is_inactive){
                $query->where('status', $request->is_inactive=="true" ? '0' : '1');
            }

            if ($request->boolean('active_only')) {
                $query->where(function ($q) {
                    $q->where('status', '1')->orWhere('status', 1);
                });
            }

            if ($request->boolean('contract_received_only')) {
                $query->whereHas('clientProfile', function ($q) {
                    $q->where('contract_recieved', 1)->orWhere('contract_recieved', '1');
                });
            }

            $search = trim((string) ($request->input('name') ?: $request->input('search.value', '')));
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%$search%")
                      ->orWhere('last_name', 'like', "%$search%")
                      ->orWhere('name', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%")
                      ->orWhere('phone', 'like', "%$search%")
                      ->orWhereHas('clientProfile', function ($profile) use ($search) {
                          $profile->where('quickbooks_customer_id', 'like', "%$search%");
                      });
                });
            }

            $qbId = trim((string) $request->input('qb_id', ''));
            if ($qbId !== '') {
                $query->whereHas('clientProfile', function ($profile) use ($qbId) {
                    $profile->where('quickbooks_customer_id', 'like', "%$qbId%");
                });
            }

            $total_record=$query->count();
            $clients = [];
            $start = max((int) $request->input('start', 0), 0);
            $length = min(max((int) $request->input('length', 20), 1), 50);
            $data=$query->orderByDesc('id')->offset($start)->limit($length)->get();
            if(count($data)>0){
                foreach($data as $key=>$val){
                    $profile = $val->clientProfile;
                    $displayName = trim((string) ($val->first_name ?? '').' '.(string) ($val->last_name ?? ''));
                    if ($displayName === '') {
                        $displayName = $val->name ?: 'Unnamed Bill To';
                    }
                    $parent = $profile?->parentBillTo;
                    $parentName = $parent
                        ? (trim((string) ($parent->first_name ?? '').' '.(string) ($parent->last_name ?? '')) ?: ($parent->name ?? null))
                        : null;
                    $synced = ! empty($profile?->quickbooks_customer_id);
                    $syncStatus = $profile?->qb_customer_sync_status;
                    $syncLabel = $synced ? 'Synced' : ($syncStatus ? ucfirst($syncStatus) : 'Not synced');

                    $clients[]=array(
                        'id'              => (int) $val->id,
                        'name'            => $displayName,
                        'first_name'      => $val->first_name,
                        'last_name'       => $val->last_name,
                        'email'           => $val->email,
                        'phone'           => $val->phone,
                        'parent_bill_to_id' => $profile?->parent_bill_to_id ? (int) $profile->parent_bill_to_id : null,
                        'parent_bill_to'  => $parentName,
                        'quickbooks_id'   => $profile?->quickbooks_customer_id,
                        'contract'        => ((string) ($profile?->contract_recieved ?? '0') === '1') ? 'Yes' : 'No',
                        'status'          => $val->status=='1' ? 'Active' : 'Inactive',
                        'qb_sync'         => $syncLabel,
                        'qb_sync_status'  => $syncStatus,
                        'qb_synced'       => $synced,
                        'qb_last_sync_attempt_at' => optional($profile?->qb_customer_last_sync_attempt_at)->toDateTimeString(),
                        'qb_last_sync_error'      => $profile?->qb_customer_last_sync_error,
                        'action'          => '<ion-icon name="create"  size="large" color="success" id="'.$val->id.'"></ion-icon>'
                    );
                }
            }
            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $clients,
                'recordsTotal'      => $totalBillables,
                'recordsFiltered'   => $total_record,
                'quickbooks_connected' => QuickBooksHelper::isConnected(),
                'unsynced_count'    => $this->unsyncedClientCount(),
            ]);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }

    public function bulkUpdateClients(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'updates' => 'required|array|min:1|max:500',
            'updates.*.id' => 'required|integer|distinct|exists:users,id',
            'updates.*.phone' => 'sometimes|nullable|string|max:30',
            'updates.*.status' => 'sometimes|in:0,1',
            'updates.*.address' => 'sometimes|nullable|string|max:255',
            'updates.*.city' => 'sometimes|nullable|string|max:100',
            'updates.*.state' => 'sometimes|nullable|string|max:100',
            'updates.*.zip_code' => 'sometimes|nullable|string|max:20',
            'updates.*.company_name' => 'sometimes|nullable|string|max:255',
            'updates.*.primary_billing_contact' => 'sometimes|nullable|string|max:255',
            'updates.*.default_labor_amount' => 'sometimes|nullable|numeric|min:0',
            'updates.*.default_buyback_amount' => 'sometimes|nullable|numeric|min:0',
            'updates.*.default_cups_amount' => 'sometimes|nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $validator->validated();
        $userFields = ['phone', 'status'];
        $profileFields = [
            'address',
            'city',
            'state',
            'zip_code',
            'company_name',
            'primary_billing_contact',
            'default_labor_amount',
            'default_buyback_amount',
            'default_cups_amount',
        ];

        DB::transaction(function () use ($validated, $userFields, $profileFields): void {
            foreach ($validated['updates'] as $change) {
                $client = User::query()->where('role_id', 3)->whereKey((int) $change['id'])->firstOrFail();

                $userPayload = array_intersect_key($change, array_flip($userFields));
                if ($userPayload !== []) {
                    $client->update($userPayload);
                }

                $profilePayload = array_intersect_key($change, array_flip($profileFields));
                if ($profilePayload !== []) {
                    $profile = $client->clientProfile ?: new Client(['user_id' => $client->id]);
                    $profile->fill($profilePayload);
                    $profile->save();
                }
            }
        });

        return $this->successResponse(count($validated['updates']).' Bill To record(s) updated.', [
            'updated' => count($validated['updates']),
        ]);
    }

    public function syncAllClientsQuickBooks(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        if (! QuickBooksHelper::isConnected()) {
            return $this->errorResponse('Please connect to QuickBooks before syncing Bill To records.', 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        SyncAllClientsWithQuickBooks::dispatch();

        return $this->successResponse('QuickBooks sync started. Clients will be synced in the background.', [
            'unsynced_count' => $this->unsyncedClientCount(),
        ]);
    }

    public function exportClients(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $query = User::query()
            ->where('role_id', 3)
            ->with('clientProfile');

        if ($request->boolean('active_only')) {
            $query->where(function ($q) {
                $q->where('status', '1')->orWhere('status', 1);
            });
        }

        if ($request->boolean('contract_received_only')) {
            $query->whereHas('clientProfile', function ($q) {
                $q->where('contract_recieved', 1)->orWhere('contract_recieved', '1');
            });
        }

        $search = trim((string) $request->input('name', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%");
            });
        }

        $qbId = trim((string) $request->input('qb_id', ''));
        if ($qbId !== '') {
            $query->whereHas('clientProfile', function ($profile) use ($qbId) {
                $profile->where('quickbooks_customer_id', 'like', "%$qbId%");
            });
        }

        $rows = $query->orderByDesc('id')->get()->map(function ($client) {
            $name = trim((string) ($client->first_name ?? '').' '.(string) ($client->last_name ?? ''));
            if ($name === '') {
                $name = $client->name ?: 'Unnamed Bill To';
            }
            $profile = $client->clientProfile;

            return [
                'Name' => $name,
                'Email' => $client->email,
                'Phone' => $client->phone,
                'QuickBooks ID' => $profile?->quickbooks_customer_id,
                'Status' => ((string) $client->status === '1' || (int) $client->status === 1) ? 'Active' : 'Inactive',
                'Contract Received' => ((string) ($profile?->contract_recieved ?? '0') === '1') ? 'Yes' : 'No',
                'Address' => $profile?->address,
            ];
        })->values();

        return $this->successResponse('Bill To export data fetched successfully.', [
            'rows' => $rows,
        ]);
    }

    private function unsyncedClientCount(): int
    {
        return User::query()
            ->where('role_id', 3)
            ->whereHas('clientProfile', function ($profile) {
                $profile->whereNull('quickbooks_customer_id')->orWhere('quickbooks_customer_id', '');
            })
            ->count();
    }

    public function getClientParentOptions(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $options = User::query()
            ->where('role_id', 3)
            ->select(['id', 'first_name', 'last_name', 'name'])
            ->orderBy('name')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(function ($client) {
                $name = trim((string) ($client->first_name ?? '').' '.(string) ($client->last_name ?? ''));

                return [
                    'id' => (int) $client->id,
                    'name' => $name !== '' ? $name : ($client->name ?: 'Unnamed Bill To'),
                ];
            })
            ->values();

        return $this->successResponse('Parent Bill To options fetched successfully', $options);
    }

    public function updateClientParent(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'client_id' => 'required|integer|exists:users,id',
            'parent_bill_to_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $client = User::query()->where('role_id', 3)->find($request->client_id);
        if (! $client) {
            return $this->errorResponse('Invalid Bill To client.', 'ERROR', Response::HTTP_NOT_FOUND);
        }

        $parentId = $request->input('parent_bill_to_id');
        if ($parentId !== null && (int) $parentId === (int) $client->id) {
            return $this->errorResponse('A Bill To cannot be its own parent.', 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($parentId !== null) {
            $parent = User::query()->where('role_id', 3)->with('clientProfile')->find($parentId);
            if (! $parent) {
                return $this->errorResponse('Invalid parent Bill To.', 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            if ((int) ($parent->clientProfile?->parent_bill_to_id ?? 0) === (int) $client->id) {
                return $this->errorResponse('This parent selection would create a Bill To loop.', 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $profile = $client->clientProfile ?: new Client(['user_id' => $client->id]);
        $profile->parent_bill_to_id = $parentId ? (int) $parentId : null;
        $profile->save();

        return $this->successResponse('Parent Bill To updated successfully', [
            'client_id' => (int) $client->id,
            'parent_bill_to_id' => $profile->parent_bill_to_id,
        ]);
    }

    public function syncClientQuickBooks(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'client_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! QuickBooksHelper::isConnected()) {
            return $this->errorResponse('Please connect to QuickBooks before syncing this Bill To record.', 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $client = User::query()->where('role_id', 3)->with('clientProfile')->find($request->client_id);
        if (! $client) {
            return $this->errorResponse('Only clients can be synced to QuickBooks.', 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $profile = $client->clientProfile ?: new Client(['user_id' => $client->id]);
        if ($profile->quickbooks_customer_id) {
            return $this->successResponse('Client is already synced with QuickBooks.', [
                'client_id' => (int) $client->id,
                'quickbooks_id' => $profile->quickbooks_customer_id,
            ]);
        }

        CreateQuickBooksCustomer::dispatch($client->id, [
            'name'         => $client->name,
            'company_name' => $client->name,
            'email'        => $client->email,
            'phone'        => $client->phone,
            'address'      => $profile->address ?? $client->address,
            'cc_emails'    => $profile->cc_emails ?? [],
        ]);

        $profile->qb_customer_sync_status = 'queued';
        $profile->qb_customer_last_sync_error = null;
        $profile->save();

        return $this->successResponse('Client QuickBooks sync queued.', [
            'client_id' => (int) $client->id,
            'qb_sync' => 'Queued',
        ]);
    }

    public function getClientMonthlyStats(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'client_id' => 'required|integer|exists:users,id',
            'month' => 'nullable|date_format:Y-m',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $client = User::query()->where('role_id', 3)->find($request->client_id);
        if (! $client) {
            return $this->errorResponse('Invalid Bill To client.', 'ERROR', Response::HTTP_NOT_FOUND);
        }

        $month = $request->input('month', now()->format('Y-m'));
        $start = Carbon::parse($month.'-01')->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        $base = Event::query()
            ->whereBetween('start_time', [$start, $end])
            ->where(function ($q) use ($client) {
                $q->where('client_id', $client->id)
                    ->orWhereHas('billingParties', fn ($bq) => $bq->where('client_id', $client->id));
            });

        return $this->successResponse('Monthly summary fetched successfully', [
            'month'        => $start->format('F Y'),
            'submitted'    => (clone $base)->whereHas('recap')->count(),
            'not_recapped' => (clone $base)->where('recap_needed', 1)->where(fn ($q) => $q->whereDoesntHave('recap')->orWhere('recap_status', 'overdue'))->count(),
            'no_shows'     => (clone $base)->where(fn ($q) => $q->where('admin_status', 'no_show')->orWhereHas('recap', fn ($rq) => $rq->where('manager_no_show', 1)))->count(),
            'late'         => (clone $base)->whereHas('recap', fn ($rq) => $rq->whereRaw('recaps.created_at > DATE_ADD(events.end_time, INTERVAL 24 HOUR)'))->count(),
            'cancelled'    => (clone $base)->where('admin_status', 'like', 'cancelled_%')->count(),
        ]);
    }

    public function getClientDetails(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'client_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $client = User::query()
            ->where('role_id', 3)
            ->with('clientProfile.parentBillTo:id,first_name,last_name,name')
            ->find($request->client_id);

        if (! $client) {
            return $this->errorResponse('Invalid Bill To client.', 'ERROR', Response::HTTP_NOT_FOUND);
        }

        $profile = $client->clientProfile;
        $name = trim((string) ($client->first_name ?? '').' '.(string) ($client->last_name ?? ''));
        if ($name === '') {
            $name = $client->name ?: 'Unnamed Bill To';
        }
        $parent = $profile?->parentBillTo;
        $parentName = $parent
            ? (trim((string) ($parent->first_name ?? '').' '.(string) ($parent->last_name ?? '')) ?: ($parent->name ?? null))
            : null;
        $qbSynced = ! empty($profile?->quickbooks_customer_id);
        $qbStatus = $profile?->qb_customer_sync_status;

        return $this->successResponse('Bill To details fetched successfully', [
            'id' => (int) $client->id,
            'name' => $name,
            'first_name' => $client->first_name,
            'last_name' => $client->last_name,
            'company_name' => $profile?->company_name,
            'primary_billing_contact' => $profile?->primary_billing_contact,
            'email' => $client->email,
            'phone' => $client->phone,
            'status' => ((string) $client->status === '1' || (int) $client->status === 1) ? 'Active' : 'Inactive',
            'status_value' => (string) ($client->status ?? '1'),
            'created_at' => optional($client->created_at)->format('d M Y'),
            'parent_bill_to' => $parentName,
            'brand_ids' => $profile?->brands?->pluck('id')->map(fn ($id) => (int) $id)->values()->all() ?? [],
            'distributor_ids' => $profile?->distributors?->pluck('id')->map(fn ($id) => (int) $id)->values()->all() ?? [],
            'cc_emails' => $profile?->cc_emails ?? [],
            'contract_recieved' => (string) ($profile?->contract_recieved ?? '0'),
            'default_receipt_percent' => $profile?->default_receipt_percent,
            'address' => [
                'address' => $profile?->address,
                'city' => $profile?->city,
                'state' => $profile?->state,
                'zip_code' => $profile?->zip_code,
                'country' => $profile?->country,
            ],
            'quickbooks' => [
                'customer_id' => $profile?->quickbooks_customer_id,
                'synced' => $qbSynced,
                'status' => $qbStatus,
                'label' => $qbSynced ? 'Synced' : ($qbStatus ? ucfirst($qbStatus) : 'Not synced'),
                'last_attempt_at' => optional($profile?->qb_customer_last_sync_attempt_at)->format('m/d/Y H:i'),
                'last_error' => $profile?->qb_customer_last_sync_error,
                'connected' => QuickBooksHelper::isConnected(),
            ],
            'platform' => [
                'default_labor_amount' => $profile?->default_labor_amount,
                'default_cups_amount' => $profile?->default_cups_amount,
                'default_buyback_amount' => $profile?->default_buyback_amount,
                'notes' => $profile?->notes,
            ],
            'budget' => [
                'budget_program_name' => $profile?->budget_program_name,
                'budget_max_amount' => $profile?->budget_max_amount,
                'budget_max_events' => $profile?->budget_max_events,
                'budget_warn_percent' => $profile?->budget_warn_percent ?? 80,
                'budget_enforce' => (bool) ($profile?->budget_enforce ?? false),
                'budget_require_approval' => (bool) ($profile?->budget_require_approval ?? false),
                'budget_alert_email' => $profile?->budget_alert_email,
            ],
        ]);
    }

    public function getClientTransactions(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'client_id' => 'required|integer|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $client = User::query()->where('role_id', 3)->with('clientProfile')->find($request->client_id);
        if (! $client) {
            return $this->errorResponse('Invalid Bill To client.', 'ERROR', Response::HTTP_NOT_FOUND);
        }

        $profile = $client->clientProfile;
        if (! $profile?->quickbooks_customer_id) {
            return $this->successResponse('Client is not synced with QuickBooks.', [
                'client' => [
                    'id' => (int) $client->id,
                    'name' => $client->name,
                    'quickbooks_customer_id' => null,
                ],
                'transactions' => [],
                'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => 0],
                'error' => 'Client is not synced with QuickBooks.',
                'quickbooks_connected' => QuickBooksHelper::isConnected(),
            ]);
        }

        $quickBooksService = app(QuickBooksService::class);
        $allTransactions = $quickBooksService->getCustomerTransactions($profile->quickbooks_customer_id);

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $allTransactions = array_values(array_filter($allTransactions, function ($transaction) use ($request) {
                $txnDate = isset($transaction->TxnDate) ? Carbon::parse($transaction->TxnDate) : null;
                if (! $txnDate) {
                    return false;
                }
                if ($request->filled('date_from') && $txnDate->lt(Carbon::parse($request->date_from))) {
                    return false;
                }
                if ($request->filled('date_to') && $txnDate->gt(Carbon::parse($request->date_to))) {
                    return false;
                }

                return true;
            }));
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);
        $currentPage = max((int) $request->input('page', 1), 1);
        $total = count($allTransactions);
        $offset = ($currentPage - 1) * $perPage;
        $rows = array_slice($allTransactions, $offset, $perPage);

        $transactions = collect($rows)->map(function ($transaction) {
            $id = $transaction->Id ?? null;
            $balance = (float) ($transaction->Balance ?? 0);
            $localInvoice = $id ? Invoice::query()->where('qb_invoice_id', $id)->first() : null;

            return [
                'id' => $id,
                'invoice_number' => $transaction->DocNumber ?? $id,
                'date' => isset($transaction->TxnDate) ? Carbon::parse($transaction->TxnDate)->format('M d, Y') : null,
                'due_date' => isset($transaction->DueDate) ? Carbon::parse($transaction->DueDate)->format('M d, Y') : null,
                'amount' => (float) ($transaction->TotalAmt ?? 0),
                'balance' => $balance,
                'status' => $balance == 0.0 ? 'Paid' : 'Unpaid',
                'local_invoice_id' => $localInvoice?->id,
            ];
        })->values();

        return $this->successResponse('Transactions fetched successfully', [
            'client' => [
                'id' => (int) $client->id,
                'name' => $client->name,
                'quickbooks_customer_id' => $profile->quickbooks_customer_id,
            ],
            'transactions' => $transactions,
            'pagination' => [
                'current_page' => $currentPage,
                'last_page' => max((int) ceil($total / $perPage), 1),
                'per_page' => $perPage,
                'total' => $total,
            ],
            'filters' => $request->only(['date_from', 'date_to']),
            'quickbooks_connected' => QuickBooksHelper::isConnected(),
        ]);
    }

    public function syncClientTransactions(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'client_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $client = User::query()->where('role_id', 3)->with('clientProfile')->find($request->client_id);
        if (! $client?->clientProfile?->quickbooks_customer_id) {
            return $this->errorResponse('Client is not synced with QuickBooks.', 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! QuickBooksHelper::isConnected()) {
            return $this->errorResponse('Please connect to QuickBooks before syncing transactions.', 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        app(QuickBooksService::class)->syncInvoices();

        return $this->successResponse('Transactions synced successfully.', [
            'client_id' => (int) $client->id,
        ]);
    }

    public function getBrands(Request $request){
        $user = JWTAuth::user();
        if($user){
            $query = Brand::query()->with(['parent:id,brand_name', 'brandCategories:id,name']);
            $totalRecord = (clone $query)->count();
            $search = trim((string) $request->input('search.value', ''));

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('brand_name', 'like', "%{$search}%")
                        ->orWhereHas('parent', fn ($parent) => $parent->where('brand_name', 'like', "%{$search}%"))
                        ->orWhereHas('brandCategories', fn ($category) => $category->where('name', 'like', "%{$search}%"));
                });
            }

            $filteredRecord = (clone $query)->count();
            $brands = [];
            $start = max((int) $request->input('start', 0), 0);
            $length = min(max((int) $request->input('length', 20), 1), 50);
            $data = $query->orderByDesc('id')->offset($start)->limit($length)->get();
            $categoryNames = BrandCategory::query()->pluck('name', 'id');
            
            foreach($data as $val){
                $categories = $val->brandCategories->pluck('name')->values()->all();

                if (empty($categories)) {
                    $rawCategoryIds = $val->getRawOriginal('brand_category_id');
                    $categories = collect(explode(',', (string) $rawCategoryIds))
                        ->map(fn ($id) => $categoryNames->get((int) trim($id)))
                        ->filter()
                        ->values()
                        ->all();
                }
                $brands[] = [
                    'id' => (int) $val->id,
                    'name' => $val->brand_name,
                    'parent_name' => $val->parent?->brand_name,
                    'categories' => $categories,
                    'status' => $val->status == '1' ? 'Active' : 'Inactive',
                ];
            }
            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $brands,
                'recordsTotal'      => $totalRecord,
                'recordsFiltered'   => $filteredRecord,
            ]);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }

    public function deleteBrand(Request $request){
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        if (! in_array((int) $user->role_id, [1, 6], true)) {
            return $this->errorResponse('You are not authorized to delete brands.', [], 403);
        }

        $request->validate(['id' => ['required', 'integer', 'exists:brands,id']]);
        $brand = Brand::findOrFail($request->id);
        $linkedEventsCount = Event::where('brand_id', $brand->id)->count();

        if ($linkedEventsCount > 0) {
            return $this->errorResponse(
                'This brand cannot be deleted because it is linked with ' . $linkedEventsCount . ' event' . ($linkedEventsCount === 1 ? '' : 's') . '.',
                'BRAND_LINKED_WITH_EVENTS',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $brand->brandCategories()->detach();
        $brand->allowedBillToClients()->detach();
        $brand->delete();

        return $this->successResponse('Brand deleted successfully.', []);
    }

    public function getBrandCategories(Request $request){
        $user = JWTAuth::user();
        if($user){
            $query=BrandCategory::orderBy('id','DESC');
            $totalRecord = (clone $query)->count();
            $search = trim((string) $request->input('search.value', ''));
            
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%");
                });
            }
            
            $filteredRecord = (clone $query)->count();
            $start = max((int) $request->input('start', 0), 0);
            $length = min(max((int) $request->input('length', 25), 1), 50);
            
            $categories = [];
            $data=$query->offset($start)->limit($length)->get();

            if(count($data)>0){
                foreach($data as $key=>$val){
                    $categories[]=array(
                        'id'        => (int) $val->id,
                        'name'      => $val->name,          
                    );
                }
            }
            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $categories,
                'recordsTotal'      => $totalRecord,
                'recordsFiltered'   => $filteredRecord,
            ]);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function saveBrandCategory(Request $request){
        $user = JWTAuth::user();
        if($user){
            $request->validate([
                'name' => 'required|string|max:255',
                'note' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);
            $notes = $request->input('notes', $request->input('note'));
            if(isset($request->id) && !empty($request->id)){
                $temp=BrandCategory::find($request->id);
                $temp->name=$request->name;
                $temp->notes=$notes;
                $temp->save();
                return $this->successResponse('Brand Category Updated', []);
            } else {
                BrandCategory::create([
                    'name' => $request->name,
                    'notes' => $notes,
                ]);
                return $this->successResponse('Brand Category Data Saved', []);
            }
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }

    public function deleteBrandCategory(Request $request){
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        if (! in_array((int) $user->role_id, [1, 6], true)) {
            return $this->errorResponse('You are not authorized to delete brand categories.', [], 403);
        }

        $request->validate(['id' => ['required', 'integer', 'exists:brand_categories,id']]);
        $category = BrandCategory::findOrFail($request->id);
        $linkedBrandsCount = $category->brands()->count();

        if ($linkedBrandsCount > 0) {
            return $this->errorResponse(
                'This category cannot be deleted because it is linked with ' . $linkedBrandsCount . ' brand' . ($linkedBrandsCount === 1 ? '' : 's') . '.',
                'BRAND_CATEGORY_LINKED_WITH_BRANDS',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $category->delete();

        return $this->successResponse('Brand category deleted successfully.', []);
    }
    
    public function getBrandsAndDistributions(Request $request){
        $user = JWTAuth::user();
        if($user){
            $distributors = EventDistributor::get();
            $brands = Brand::get();
            return $this->successResponse('Data fetched Successfully', [
                'brands' => $brands,
                'distributors' => $distributors,
            ]);
        }
    }
    
    public function getRecap(Request $request){
        $user = JWTAuth::user();
        
        if($user){
            $query = Recap::with('event.brand','event.location','event.eventType','ambassador')->orderByDesc('id');
            $this->applyPosMarketScopeToRecapQuery($query, $user);
            $total_record=$query->count();
            $recap = [];
            
            if($request->filled('location_id')){
                $query->where('location_id',$request->location_id);
            }
            if($request->filled('ambassador_id')){
                $query->where('ambassador_id',$request->ambassador_id);
            }
            if($request->filled('event_id')){
                $query->where('event_id',$request->event_id);
            }
            if($request->filled('state_id')){
                $query->whereHas('event.location', function($q) use ($request) {
                    $q->where('state', $request->state_id);
                });
            }
            
            if ($request->filled('ambassador_email')) {
                $query->where('ambassador_id', $request->ambassador_email);  
            }
            if ($request->filled('ambassador_name')) {
                $query->where('ambassador_id', $request->ambassador_name);
            }
            
            // Nested
            if ($request->filled('department_id')) {
                $query->whereHas('event', function($q) use ($request) {
                    $q->where('department_id', $request->department_id);
                });
            }
            if ($request->filled('manager_id')) {
                $query->whereHas('event', function($q) use ($request) {
                    $q->where('manager_id', $request->manager_id);
                });
            }
            if ($request->filled('event_type_id')) {
                $query->whereHas('event', function($q) use ($request) {
                    $q->where('event_type_id', $request->event_type_id);
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
            if ($request->filled('market_id')) {
                $query->whereHas('event', function($q) use ($request) {
                    $q->where('market_id', $request->market_id);
                });
            }
            if ($request->filled('venue_id')) {
                $query->whereHas('event', function($q) use ($request) {
                    $q->where('event_location_id', $request->venue_id);
                });
            }
            if ($request->filled('event_location_id')) {
                $query->whereHas('event', function($q) use ($request) {
                    $q->where('event_location_id', $request->event_location_id);
                });
            }
            if ($request->filled('region')) {
                $query->whereHas('event', function($q) use ($request) {
                    $q->where('region', $request->region);
                });
            }
            if ($request->filled('distributor_id')) {
                $query->whereHas('event', function($q) use ($request) {
                    $q->where('distributor_id', $request->distributor_id);
                });
            }
            if ($request->filled('bill_id')) {
                $query->whereHas('event', function($q) use ($request) {
                    $q->where('client_id', $request->bill_id);
                });
            }
            if ($request->filled('recap_needed')) {
                $query->whereHas('event', function($q) use ($request) {
                    $q->where('recap_needed', $request->recap_needed);
                });
            }

            $search = is_array($request->search)
                ? ($request->search['value'] ?? '')
                : $request->search;
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                        ->orWhere('event_id', 'like', "%{$search}%")
                        ->orWhereHas('event', function ($eventQuery) use ($search) {
                            $eventQuery->where('event_name', 'like', "%{$search}%")
                                ->orWhereHas('brand', function ($brandQuery) use ($search) {
                                    $brandQuery->where('brand_name', 'like', "%{$search}%");
                                });
                        })
                        ->orWhereHas('ambassador', function ($ambassadorQuery) use ($search) {
                            $ambassadorQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }

            
            if ($request->filled('startDate') && $request->filled('endDate')) {
                $startDate = $request->startDate;
                $endDate = $request->endDate;
                $query->whereHas('event', function($q) use ($startDate, $endDate) {
                    $q->where(function($q) use ($startDate, $endDate) {
                        $q->whereBetween('start_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                          ->orWhereBetween('end_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                          ->orWhere(function($subQ) use ($startDate, $endDate) {
                              $subQ->where('start_time', '<=', $startDate . ' 00:00:00')
                                    ->where('end_time', '>=', $endDate . ' 23:59:59');
                          });
                    });
                });
            }

            $filtered_record = $query->count();
            if ($request->filled('length') && (int) $request->length !== -1) {
                $data = $query
                    ->offset(max(0, (int) $request->input('start', 0)))
                    ->limit(max(1, (int) $request->length))
                    ->get();
            } else {
                $data = $query->get();
            }
            
            if(count($data)>0){
                foreach($data as $key=>$val){
                    $recap[]=array(
                        'id'        => $val->id,
                        'event_id'  => $val->event_id,
                        'brand'     => $val->event->brand->brand_name ?? '',
                        'event'     => $val->event->event_name ?? '',
                        'city'      => $val->event->location->city ?? '', 
                        'date'      => $val->event->start_time ?? '',
                        'ambassador'=> $val->ambassador->name ?? '',
                        'status'    => $val->is_approved ? 'Approved' : (!empty($val->denied_at) ? 'Denied' : 'Pending'),
                        'action'    => '<ion-icon name="eye"  size="large" color="primary" id="'.$val->id.'"></ion-icon><ion-icon name="create"  size="large" color="success" id="'.$val->id.'"></ion-icon>'
                    );
                }
            }
            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $recap,
                'recordsTotal'      => $total_record,
                'recordsFiltered'   => $filtered_record,
            ]);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }

    public function exportRecaps(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $filters = [
            'department_id' => $request->input('department_id'),
            'market_id' => $request->input('market_id'),
            'manager_id' => $request->input('manager_id'),
            'venue_id' => $request->input('venue_id'),
            'distributor_id' => $request->input('distributor_id'),
            'location_id' => $request->input('location_id'),
            'location_state' => $request->input('location_state'),
            'brand_id' => $request->input('brand_id'),
            'ambassador_name_id' => $request->input('ambassador_name_id'),
            'ambassador_email_id' => $request->input('ambassador_email_id'),
            'event_id' => $request->input('event_id'),
            'startDate' => $request->input('startDate'),
            'endDate' => $request->input('endDate'),
            'overview_needed' => $request->input('overview_needed') == 1,
        ];

        $filters = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });

        $timestamp = now()->format('Y-m-d_H-i-s');

        return Excel::download(new AdminRecapEvent($filters), "events_recap_export_{$timestamp}.xlsx");
    }

    public function emailRecapExport(Request $request, RecapExportService $recapExportService, ?int $id = null)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $id = $id ?: (int) $request->input('id');
        if (! $id) {
            return $this->errorResponse('Recap not found', [], 404);
        }

        $recap = Recap::with(['event.brand', 'event.location'])->find($id);
        if (! $recap) {
            return $this->errorResponse('Recap not found', [], 404);
        }

        try {
            $this->authorizeRecapExportForUser($user, $recap);
            $recapExportService->emailExportToUser($user, $recap);
        } catch (\Throwable $e) {
            if (! $e instanceof RuntimeException) {
                report($e);
            }

            return $this->errorResponse(
                $e instanceof RuntimeException
                    ? $e->getMessage()
                    : 'We could not send the recap export email. Please try again or contact support.',
                [],
                422
            );
        }

        return $this->successResponse(
            'Recap export has been emailed to '.$user->email.'. If you do not see it within a few minutes, check your spam or junk folder.',
            []
        );
    }

    private function authorizeRecapExportForUser(User $user, Recap $recap): void
    {
        $roleId = (string) ($user->role_id ?? '');

        if (in_array($roleId, ['1', '6'], true)) {
            return;
        }

        if ($roleId === '3' && $recap->event && (int) $recap->event->client_id === (int) $user->id) {
            return;
        }

        throw new RuntimeException('You are not authorized to export this recap.');
    }

    public function searchRecapFilters(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $type = (string) $request->input('type');
        $search = trim((string) $request->input('q', $request->input('search', '')));
        $limit = min(max((int) $request->input('limit', 20), 1), 50);

        if (mb_strlen($search) < 2) {
            return $this->successResponse('Recap filter options fetched', []);
        }

        switch ($type) {
            case 'departments':
                $results = EventDepartment::query()
                    ->select('id', 'name')
                    ->where('name', 'like', "%{$search}%")
                    ->orderBy('name')
                    ->limit($limit)
                    ->get();
                break;

            case 'markets':
                $query = Market::query()->select('id', 'name')->active();
                $allowedMarketIds = $this->posUserAllowedMarketIds($user);
                if ($allowedMarketIds !== null) {
                    $query->whereIn('id', $allowedMarketIds);
                }
                $results = $query
                    ->where('name', 'like', "%{$search}%")
                    ->ordered()
                    ->limit($limit)
                    ->get();
                break;

            case 'managers':
                $results = User::query()
                    ->select('id', 'name', 'first_name', 'last_name', 'email')
                    ->where('role_id', 6)
                    ->where('status', '1')
                    ->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orderBy('name')
                    ->limit($limit)
                    ->get();
                break;

            case 'event_types':
                $results = EventType::query()
                    ->select('id', 'name')
                    ->where('name', 'like', "%{$search}%")
                    ->orderBy('name')
                    ->limit($limit)
                    ->get();
                break;

            case 'distributors':
                $results = EventDistributor::query()
                    ->select('id', 'name')
                    ->where('name', 'like', "%{$search}%")
                    ->orderBy('name')
                    ->limit($limit)
                    ->get();
                break;

            case 'events':
                $query = Event::query()
                    ->select('id', 'event_name')
                    ->where(function ($q) use ($search) {
                        $q->where('event_name', 'like', "%{$search}%")
                            ->orWhere('id', $search);
                    });
                $this->applyPosMarketScopeToEvents($query, $user);
                $results = $query
                    ->orderByRaw('start_time IS NULL, start_time DESC, id DESC')
                    ->limit($limit)
                    ->get();
                break;

            case 'states':
                $results = EventLocation::query()
                    ->select('id', 'state')
                    ->whereNotNull('state')
                    ->where('state', 'like', "%{$search}%")
                    ->orderBy('state')
                    ->limit($limit)
                    ->get()
                    ->map(fn ($row) => ['id' => $row->id, 'state' => $row->state, 'name' => $row->state])
                    ->values();
                break;

            case 'event_locations':
                $results = EventLocation::query()
                    ->select('id', 'name', 'address', 'city', 'state')
                    ->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('address', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%")
                            ->orWhere('state', 'like', "%{$search}%");
                    })
                    ->orderBy('name')
                    ->limit($limit)
                    ->get();
                break;

            case 'ambassador_names':
            case 'ambassador_emails':
                $results = User::query()
                    ->select('id', 'name', 'first_name', 'last_name', 'email')
                    ->where('role_id', 5)
                    ->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orderBy($type === 'ambassador_emails' ? 'email' : 'name')
                    ->limit($limit)
                    ->get();
                break;

            case 'brands':
                $results = Brand::query()
                    ->select('id', 'brand_name')
                    ->where('brand_name', 'like', "%{$search}%")
                    ->orderBy('brand_name')
                    ->limit($limit)
                    ->get();
                break;

            case 'clients':
                $results = User::query()
                    ->select('id', 'name', 'first_name', 'last_name', 'email')
                    ->where('role_id', 3)
                    ->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orderBy('name')
                    ->limit($limit)
                    ->get();
                break;

            default:
                return $this->errorResponse('Invalid recap filter type', [], 422);
        }

        return $this->successResponse('Recap filter options fetched', $results);
    }
    
    public function getAmbassador(Request $request){
        $user = JWTAuth::user();
        
        $locations = User::select('id', 'name')->where('role_id', 4)->whereNotNull('name')->orderBy('name')->get();
        $markets = $this->marketsForUser($user);
        $brands = Brand::get();
        $managers = User::where('role_id', 6)->where('status', '1')->get();
        $ambassadors = User::select('id', 'name', 'email')->where('role_id', 5)->orderBy('name')->get();
        
        $events = Event::with('brand', 'location', 'eventType', 'client', 'recap','assignAmbassadors','assignAmbassadors.ambassador')
                    ->where('event_status', '1')
                    // Previous requirement (chronological ascending — kept for easy restore):
                    // ->orderByRaw('booked_date IS NULL, booked_date DESC, start_time ASC');
                    // Latest client/PM requirement — descending by event date (farthest/newest first):
                    ->orderByRaw('start_time IS NULL, start_time DESC, id DESC');
                    
        $states = EventLocation::orderBy('state')->get();
        $clients = User::where('role_id', 3)->get();
        $venueTypes = Venue::get();
        $departments = EventDepartment::get();
        $distributors = EventDistributor::get();
        
        if($user){
            $query = User::where('role_id', 5)
            ->with(['lastEvent' => function($q) {
                $q->select('events.id','event_name','end_time')
                  ->orderBy('end_time','desc');
            }])
            ->orderBy('id','DESC');
            $recordsTotal = (clone $query)->count();

            $globalSearch = trim((string) $request->input('search.value', ''));
            if ($globalSearch !== '') {
                $query->where(function ($q) use ($globalSearch) {
                    $q->where('first_name', 'like', '%' . $globalSearch . '%')
                      ->orWhere('last_name', 'like', '%' . $globalSearch . '%')
                      ->orWhere('name', 'like', '%' . $globalSearch . '%')
                      ->orWhere('email', 'like', '%' . $globalSearch . '%');
                });
            }
            
            if($request->filled('first_name')){
                $query->where('first_name', 'like', '%' . $request->first_name . '%');
            }
            if($request->filled('last_name')){
                $query->where('last_name', 'like', '%' . $request->last_name . '%');
            }
            if($request->filled('phone')){
                $query->where('phone', 'like', '%' . $request->phone . '%');
            }
            if($request->filled('email')){
                $query->where('email', 'like', '%' . $request->email . '%');
            }
            if($request->filled('city')){
                $query->where('city', 'like', '%' . $request->city . '%');
            }
            if($request->filled('state')){
                $query->where('state', 'like', '%' . $request->state . '%');
            }
            if($request->filled('market_id')){
                $query->whereHas('ambassadorProfile.markets', fn($q) =>
                    $q->where('markets.id', $request->market_id)
                );
            }
            if($request->filled('region')){
                $query->whereHas('ambassadorProfile', fn($q) =>
                    $q->where('region', $request->region)
                );
            }
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereHas('ambassadorProfile', fn($q) =>
                    $q->whereBetween('hired', [$request->start_date, $request->end_date])
                );
            }
            // Liquor license filter (matches web)
            if ($request->filled('liquor_license')) {
                if ($request->liquor_license === '1') {
                    $query->whereHas('ambassadorLiquorLicense', fn($q) =>
                        $q->where('has_liquor_license', true)
                    );
                } elseif ($request->liquor_license === '0') {
                    $query->where(fn($q) =>
                        $q->whereDoesntHave('ambassadorLiquorLicense')
                          ->orWhereHas('ambassadorLiquorLicense', fn($sub) =>
                              $sub->where('has_liquor_license', false)
                          )
                    );
                }
            }
            // Bank setup filter (matches web)
            if ($request->filled('bank_setup') && $request->bank_setup !== 'all') {
                $query->bankSetupStatus($request->bank_setup);
            }
    
            if ($request->filled('status')) {
                if ($request->status == '1') {
                    $query->where('status', '1');
                } elseif ($request->status == '0') {
                    $query->where('status', '0');
                }
            }
            
            // if($request->filled('documents')){
            // }
            // if($request->filled('videoWatched')){
            // }
            // if($request->filled('hasUnwatchedVideos')){
            // }
            // if($request->filled('comdataCardSent')){
            // }

            $total_record=$query->count();
            $recap = [];
            $data=$query->offset($request->start)->limit(25)->get();
            
            if(count($data)>0){
                foreach($data as $key=>$val){
                    $recap[]=array(
                        'id'            => $val->id,
                        'name'          => $val->name,
                        'email'         => $val->email,
                        'phone'         => $val->phone,
                        'city'          => $val->city,
                        'status'        => (string) $val->status,
                        'profile_image' => $val->profile_image
                            ? asset(ltrim($val->profile_image, '/'))
                            : null,
                    );
                }
            }
            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $recap,
                'recordsTotal'      => $recordsTotal,
                'recordsFiltered'   => $total_record,
                'locations'         => $locations,
                'markets'           => $markets,
                'brands'            => $brands,
                'managers'          => $managers,
                'ambassadors'       => $ambassadors,
                'events'            => $events,
                'states'            => $states,
                'clients'           => $clients,
                'venueTypes'        => $venueTypes,
                'departments'       => $departments,
                'distributors'      => $distributors,
            ]);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function getEventDepartment(Request $request){
        $user = JWTAuth::user();
        if($user){
            $query = EventDepartment::orderBy('id','ASC');
            if ($request->search['value']) {
                $search = $request->search['value'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%");
                });
            }
            
            $total_record=$query->count();
            $department = [];
            $data=$query->offset($request->start)->limit(25)->get();
            
            if(count($data)>0){
                foreach($data as $key=>$val){
                    $department[]=array(
                        'id'            => $val->id,
                        'name'          => $val->name,
                        'description'   => $val->description, 
                        // 'action'        => '<ion-icon name="eye"  size="large" color="primary" id="'.$val->id.'"></ion-icon><ion-icon name="create"  size="large" color="success" id="'.$val->id.'"></ion-icon>'
                        'action'        => '<ion-icon name="create"  size="large" color="success" id="'.$val->id.'"></ion-icon>'
                    );
                }
            }
            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $department,
                'recordsTotal'      => $total_record,
                'recordsFiltered'   => $total_record,
            ]);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function getBrandCategory(){
        $user = JWTAuth::user();
        if($user){
            $brandsCategory = BrandCategory::get();
            return $this->successResponse('Brand Category Data', $brandsCategory);
        }
    }
    
    public function getBrandById(Request $request){
        if(isset($request->id)){
            $brand = Brand::where('id',$request->id)->with(['user', 'brandCategories:id'])->first();
            $brand->brand_category_ids = $brand->brandCategories->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
            return $this->successResponse('Brand Data', $brand);
        }
    }
    
    public function addUpdateBrand(Request $request){
        $user = JWTAuth::user();
        $brandId = $request->id ? (int) $request->id : null;

        $brandNameRule = $brandId
            ? Rule::unique('brands', 'brand_name')->ignore($brandId)
            : Rule::unique('brands', 'brand_name');

        $parentRules = ['nullable', 'integer', 'exists:brands,id'];
        if ($brandId) {
            $parentRules[] = Rule::notIn([$brandId]);
        }

        $request->validate([
            'brand_name' => ['required', 'string', 'max:255', $brandNameRule],
            'parent_brand_id' => $parentRules,
            'notes' => ['nullable', 'string'],
            'brand_category_id' => ['nullable'],
            'status' => ['nullable', 'in:0,1'],
        ]);

        if(isset($request->id)){
            $brand = Brand::find($request->id);
        }else{
            $brand = new Brand();
        }
        $categoryIds = collect(explode(',', (string) $request->brand_category_id))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $brand->brand_name = $request->brand_name;
        $parentId = $request->input('parent_brand_id') ?: null;
        if ($brandId && $parentId) {
            $desc = Brand::descendantIdsIncludingSelfById($brandId);
            if (in_array((int) $parentId, array_diff($desc, [$brandId]), true)) {
                return $this->errorResponse('Invalid parent brand selection.', 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
            }
        }
        $brand->parent_brand_id = $parentId;
        $brand->notes = $request->notes;
        $brand->brand_category_id = $categoryIds[0] ?? null;
        $brand->status = $request->status;
        $brand->save();
        $brand->brandCategories()->sync($categoryIds);
        if(isset($request->id)){
            return $this->successResponse('Updated Brand Successfully', $brand);
        }else{
            return $this->successResponse('Created Brand Successfully', $brand);
        }
    }
    
    public function getDepartmentById(Request $request){
        $user = JWTAuth::user();
        if(isset($request->id)){
            $department = EventDepartment::where('id',$request->id)->first();
            return $this->successResponse('Department Data', $department);
        }
    }
    
    public function addUpdateDepartment(Request $request){
        $user = JWTAuth::user();
        if(isset($request->id)){
            $brand = EventDepartment::find($request->id);
        }else{
            $brand = new EventDepartment();
        }
        $brand->name = $request->name;
        $brand->description = $request->description;
        $brand->save();
        if(isset($request->id)){
            return $this->successResponse('Updated Department Successfully', $brand);
        }else{
            return $this->successResponse('Created Department Successfully', $brand);
        }
    }

    public function deleteDepartment(Request $request){
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $department = EventDepartment::find($request->id);
        if (! $department) {
            return $this->errorResponse('Department not found', 'ERROR', Response::HTTP_NOT_FOUND, []);
        }

        if ($department->events()->exists()) {
            return $this->errorResponse('This department is linked with existing events and cannot be deleted.', 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, []);
        }

        $department->delete();

        return $this->successResponse('Department deleted successfully.', []);
    }
    
    public function getLocations(Request $request){
        $user = JWTAuth::user();
        if($user){
            $query=EventLocation::orderBy('id', 'DESC');
            $total_record = EventLocation::count();

            $search = is_array($request->search) ? ($request->search['value'] ?? '') : $request->search;
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('address', 'like', "%$search%")
                        ->orWhere('city', 'like', "%$search%")
                        ->orWhere('state', 'like', "%$search%")
                        ->orWhere('zip_code', 'like', "%$search%");
                    });
            }
            
            $filtered_record=$query->count();
            $brands = [];
            $length = (int) $request->get('length', 20);
            $length = $length > 0 ? min($length, 50) : 20;
            $data=$query->offset((int) $request->get('start', 0))->limit($length)->get();
            
            if(count($data)>0){
                foreach($data as $key=>$val){
                    $brands[]=array(
                        'id'      => $val->id,
                        'address'      => $val->address,
                        'city'    => $val->city,
                        'state'    => $val->state,
                        'zip_code'    => $val->zip_code,
                        'action'    => $val->id
                    );
                }
            }
            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $brands,
                'recordsTotal'      => $total_record,
                'recordsFiltered'   => $filtered_record,
            ]);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function getLocationById(Request $request){
        $user = JWTAuth::user();
        if(isset($request->id)){
            $department = EventLocation::where('id',$request->id)->first();
            return $this->successResponse('Event Location', $department);
        }        
    }

    public function deleteLocation(Request $request){
        $user = JWTAuth::user();
        if($user){
            $Validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:event_locations,id',
            ]);
            if ($Validator->fails()) {
                $errors = $Validator->errors();
                foreach ($errors->all() as $key => $value) {
                    return $this->errorResponse(ucfirst($value), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
                }
            }

            $eventLocation = EventLocation::find($request->id);
            $linkedEvents = Event::where('event_location_id', $eventLocation->id)->exists();

            if ($linkedEvents) {
                return $this->errorResponse('This location is linked with an event and cannot be deleted.', 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
            }

            $eventLocation->delete();

            return $this->successResponse('Location deleted successfully.', []);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function addUpdateLocation(Request $request){
        $user = JWTAuth::user();
        if(isset($request->id)){
            $eventLocation = EventLocation::find($request->id);
        }else{
            $eventLocation = new EventLocation();
        }
        $eventLocation->name = $request->name;
        $eventLocation->store_number = $request->store_number;
        $eventLocation->phone = $request->phone;
        $eventLocation->location_payment_type = $request->location_payment_type;
        $eventLocation->address = $request->address;
        $eventLocation->state = $request->state;
        $eventLocation->city = $request->city;
        $eventLocation->latitude = $request->latitude;
        $eventLocation->longitude = $request->longitude;
        $eventLocation->internal_notes = $request->internal_notes;

        $venuePayload = app(EventLocationService::class)->applyTimezoneToVenueData([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);
        $eventLocation->timezone = $venuePayload['timezone'] ?? null;
        $eventLocation->timezone_label = $venuePayload['timezone_label'] ?? null;
        $eventLocation->save();
        if(isset($request->id)){
            return $this->successResponse('Updated Event Location Successfully', $eventLocation);
        }else{
            return $this->successResponse('Created Event Location Successfully', $eventLocation);
        }
    }
    
    public function getVenues(Request $request){
        $user = JWTAuth::user();
        if($user){
            $query=Venue::orderBy('id', 'DESC');
            if ($request->search['value']) {
                $search = $request->search['value'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%");
                });
            }
            $total_record=$query->count();
            $venues = [];
            $data=$query->offset($request->start)->limit(25)->get();
            
            if(count($data)>0){
                foreach($data as $key=>$val){
                    $venues[]=array(
                        'name'      => $val->name,
                        'description'    => $val->description,
                        'action'    => '<ion-icon name="create"  size="large" color="success" id="'.$val->id.'"></ion-icon>'
                    );
                }
            }
            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $venues,
                'recordsTotal'      => $total_record,
                'recordsFiltered'   => $total_record,
            ]);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function getVenueById(Request $request){
       $user = JWTAuth::user();
        if(isset($request->id)){
            $venue = Venue::where('id',$request->id)->first();
            return $this->successResponse('Venue Data', $venue);
        }  
    }
    
    public function addUpdateVenue(Request $request){
        $user = JWTAuth::user();
        if(isset($request->id)){
            $venue = Venue::find($request->id);
        }else{
            $venue = new Venue();
        }
        $venue->name = $request->name;
        $venue->description = $request->description;
        
        $venue->save();
        if(isset($request->id)){
            return $this->successResponse('Updated Event Location Successfully', $venue);
        }else{
            return $this->successResponse('Created Event Location Successfully', $venue);
        }
    }
     
    public function getUserLocations(Request $request){
        $user = JWTAuth::user();
        if($user){
            $query=User::orderBy('id', 'ASC')->where('role_id',4);
            if ($request->search['value']) {
                $search = $request->search['value'];
                $query->where(function ($q) use ($search) {
                    $q->where('address', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            if(isset($request->is_inactive) && !empty($request->is_inactive)){
                if($request->is_inactive=='true'){
                    $query->where('status', '0');   
                }
            }
            
            $total_record=$query->count();
            $users = [];
            $data=$query->offset($request->start)->limit(10)->get();
            
            if(count($data)>0){
                foreach($data as $key=>$val){
                    $users[]=array(
                        'name'      => $val->name,
                        'city'    => $val->city,
                        'action'    => '<ion-icon name="create"  size="large" color="success" id="'.$val->id.'"></ion-icon>'
                    );
                }
            }
            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $users,
                'recordsTotal'      => $total_record,
                'recordsFiltered'   => $total_record,
            ]);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function getUserById(Request $request){
        $user = JWTAuth::user();
        if (!$request->has('id')) {
            return $this->errorResponse('User ID is required', 400);
        }

        $userData = User::with('ambassadorProfile.markets')
            ->find($request->id);

        if (!$userData) {
            return $this->errorResponse('User not found', 404);
        }

        $profile = $userData->ambassadorProfile;

        // Build a flat payload the mobile form can patchValue() directly.
        // All ambassador profile fields are merged at the top level so the
        // mobile does not need to dig into nested objects.
        $payload = [
            // users table
            'id'          => $userData->id,
            'first_name'  => $userData->first_name,
            'last_name'   => $userData->last_name,
            'email'       => $userData->email,
            'phone'       => $userData->phone,
            'status'      => $userData->status,

            // ambassadors table — basic info
            'nickname'    => $profile?->nickname,
            'dob'         => $profile?->dob?->format('Y-m-d'),
            'venmo'       => $profile?->venmo,
            'region'      => $profile?->region,
            'hired'       => $profile?->hired?->format('Y-m-d'),
            'wage'        => $profile?->wage,
            'shirt_size'  => $profile?->shirt_size,
            'latitude'    => $profile?->latitude,
            'longitude'   => $profile?->longitude,

            // Legal address
            'legal_address'  => $profile?->legal_address,
            'legal_city'     => $profile?->legal_city,
            'legal_state'    => $profile?->legal_state,
            'legal_zip_code' => $profile?->legal_zip_code,

            // Mailing address
            'same_as_legal'    => (bool) $profile?->same_as_legal,
            'mailing_address'  => $profile?->mailing_address,
            'mailing_city'     => $profile?->mailing_city,
            'mailing_state'    => $profile?->mailing_state,
            'mailing_zip_code' => $profile?->mailing_zip_code,

            // Certifications / flags
            'titos_trained'      => (bool) $profile?->titos_trained,
            'attended_trainings' => (bool) $profile?->attended_trainings,
            'thc_quiz_passed'    => (bool) $profile?->thc_quiz_passed,
            'tips_certified'     => (bool) $profile?->tips_certified,
            'male_ba_lgbtq_ok'   => (bool) $profile?->male_ba_lgbtq_ok,
            'has_permit'         => (bool) $profile?->has_permit,

            // About fields
            'favorite_coffee_shop'    => $profile?->favorite_coffee_shop,
            'favorite_restaurant'     => $profile?->favorite_restaurant,
            'favorite_place_to_shop'  => $profile?->favorite_place_to_shop,
            'favorite_charity'        => $profile?->favorite_charity,

            // Markets — array of IDs from the pivot (ambassador_market)
            'market_id' => $profile?->markets?->pluck('id')->toArray() ?? [],
        ];

        return $this->successResponse('User data fetched successfully', $payload);
    }
    
    public function getUserLocationData(Request $request){
        $user = JWTAuth::user();
        if($user){
            $state = State::get();
            $venue= Venue::get();
            $eventLocation = EventLocation::get();
            return $this->successResponse('Data fetched Successfully', [
                'states'          =>  $state,
                'venues'          =>    $venue,
                'eventLocations'  => $eventLocation
            ]);
        }
    }
    
    public function addUpdateUserLocation(Request $request){
        $user = JWTAuth::user();
        if (isset($request->id)) {
            $userData = User::find($request->id);
            if (!$userData) {
                return $this->errorResponse('User not found', 404);
            }
        } else {
            $userData = new User();
            $userData->email = $request->email;
        }
        $userData->address = $request->address;
        $userData->city = $request->city;
        $userData->event_location_id = $request->event_location_id;
        $userData->first_name = $request->first_name ?? $userData->first_name;
        $userData->last_name = $request->last_name ?? $userData->last_name ;
        $userData->name = $request->name ?? $userData->name ;
        $userData->latitude = $request->latitude;
        $userData->longitude = $request->longitude;
        $userData->phone = $request->phone;
        $userData->state_id = $request->state_id;
        $userData->status = $request->status;
        $userData->venue_name = $request->venue_name;
        $userData->venue_type = $request->venue_type;
        $userData->role_id = $request->role_id ?? 4;

        if ($request->filled('password')) {
            $userData->password = Hash::make($request->password);
        }
        $userData->save();
        $message = $request->id ? 'User Updated Successfully' : 'User Created Successfully';
        return $this->successResponse($message, $userData);
    }
    
    public function getMarketData(Request $request){
        $user = JWTAuth::user();
        if($user){
            $query=Market::orderBy('id', 'DESC');
            $totalQuery = Market::query();
            if ($request->filled('is_inactive')) {
                $query->where('status', $request->is_inactive === 'true'
                    ? Market::STATUS_INACTIVE
                    : Market::STATUS_ACTIVE);
                $totalQuery->where('status', $request->is_inactive === 'true'
                    ? Market::STATUS_INACTIVE
                    : Market::STATUS_ACTIVE);
            }
            $allowed = $this->posUserAllowedMarketIds($user);
            if ($allowed !== null) {
                if ($allowed === []) {
                    return $this->successResponse('Data fetched successfully', [
                        'draw' => (int) $request->draw,
                        'data' => [],
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                    ]);
                }
                $query->whereIn('id', $allowed);
                $totalQuery->whereIn('id', $allowed);
            }
            $search = null;
            if (is_array($request->search) && !empty($request->search['value'])) {
                $search = $request->search['value'];
            } elseif (!empty($request->search)) {
                $search = $request->search;
            }

            if ($search) {
                
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
                });
            }
            $total_record=$totalQuery->count();
            $filtered_record=$query->count();
            $venues = [];
            $limit = is_numeric($request->length) ? (int) $request->length : 20;
            $start = is_numeric($request->start) ? (int) $request->start : 0;
            $data=$query->offset($start)->limit($limit)->get();
            
            if(count($data)>0){
                foreach($data as $key=>$val){
                    $venues[]=array(
                        'id'      => $val->id,
                        'name'    => $val->name,
                        'location'=> $val->location,
                        'description'=> $val->description,
                        'status' => $val->status,
                        'action'    => $val->id,
                    );
                }
            }
            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $venues,
                'recordsTotal'      => $total_record,
                'recordsFiltered'   => $filtered_record,
            ]);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }

    public function deleteMarket(Request $request){
        $user = JWTAuth::user();
        if ($user && (int) $user->role_id === 7) {
            return $this->errorResponse('Unauthorized', 'forbidden', Response::HTTP_FORBIDDEN, new \stdClass());
        }
        if($user){
            $Validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:markets,id',
            ]);
            if ($Validator->fails()) {
                $errors = $Validator->errors();
                foreach ($errors->all() as $key => $value) {
                    return $this->errorResponse(ucfirst($value), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
                }
            }

            $market = Market::find($request->id);
            $market->delete();

            return $this->successResponse('Market deleted successfully.', []);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function getMarketById(Request $request){
        $user = JWTAuth::user();
        if (!$request->has('id')) {
            return $this->errorResponse('Market ID is required', 400);
        }
        $userData = Market::find($request->id);
    
        if (!$userData) {
            return $this->errorResponse('Market not found', 404);
        }
        $allowed = $this->posUserAllowedMarketIds($user);
        if ($allowed !== null && ! in_array((int) $userData->id, $allowed, true)) {
            return $this->errorResponse('Market not found', 404);
        }
        return $this->successResponse('Market data fetched successfully', $userData);
    }
    
    public function addUpdateMarket(Request $request){
        $user = JWTAuth::user();
        if ($user && (int) $user->role_id === 7) {
            return $this->errorResponse('Unauthorized', 'forbidden', Response::HTTP_FORBIDDEN, new \stdClass());
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'region' => 'nullable|string|max:255',
            'status' => ['nullable', 'in:'.implode(',', [Market::STATUS_ACTIVE, Market::STATUS_INACTIVE])],
        ]);
        if(isset($request->id)){
            $market = Market::find($request->id);
        }else{
            $market = new Market();
        }
        $market->name = $validated['name'];
        $market->location = $validated['location'] ?? null;
        $market->description = $validated['description'] ?? null;
        $market->latitude = $validated['latitude'] ?? null;
        $market->longitude = $validated['longitude'] ?? null;
        $market->region = $validated['region'] ?? null;
        $market->status = $validated['status'] ?? ($market->status ?: Market::STATUS_ACTIVE);
        
        $market->save();
        if(isset($request->id)){
            return $this->successResponse('Updated Market Successfully', $market);
        }else{
            return $this->successResponse('Created Market Successfully', $market);
        }
    }
    
    public function getDistributors(Request $request){
        $user = JWTAuth::user();
        if($user){
            $query=EventDistributor::orderBy('id', 'DESC');
            
            // Support both new mobile params (search as string) and legacy DataTables (search as array)
            $search = null;
            if (is_array($request->search) && !empty($request->search['value'])) {
                $search = $request->search['value'];
            } elseif (!empty($request->search)) {
                $search = $request->search;
            }
            
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }
            
            // Support both pagination styles
            $page = is_numeric($request->page) ? (int)$request->page : ($request->start ? (int)($request->start / $request->length + 1) : 1);
            $limit = is_numeric($request->limit) ? (int)$request->limit : ($request->length ?? 20);
            $offset = ($page - 1) * $limit;
            
            $total_record = $query->count();
            $data = $query->offset($offset)->limit($limit)->get();
            
            $venues = [];
            if(count($data)>0){
                foreach($data as $key=>$val){
                    $venues[] = [
                        'id'       => $val->id,
                        'name'     => $val->name,
                        'phone'    => $val->phone,
                        'company_name' => $val->company_name ?? null,
                        'email'    => $val->email ?? null,
                        'address'  => $val->address ?? null,
                        'type'     => $val->type ?? null,
                        'action'   => $val->id,
                    ];
                }
            }
            
            return $this->successResponse('Data fetched successfully', [
                'eventDistributors' => $venues,
                'total'             => $total_record,
                'page'              => $page,
                'limit'             => $limit,
            ]);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function getDistributorsById(Request $request){
        $user = JWTAuth::user();
        if (!$request->has('id')) {
            return $this->errorResponse('Distributor ID is required', 400);
        }
        $data = EventDistributor::find($request->id);
        if (!$data) {
            return $this->errorResponse('Distributor not found', 404);
        }
        return $this->successResponse('Distributor data fetched successfully', $data);
    }
    
    public function addUpdateDistributor(Request $request){
        $user = JWTAuth::user();
        if(isset($request->id)){
            $distributor = EventDistributor::find($request->id);
        }else{
            $distributor = new EventDistributor();
            $distributor->email = $request->email;
        }
        $distributor->address = $request->address ? $request->address : $distributor->address ;
        $distributor->company_name = $request->company_name ? $request->company_name : $distributor->company_name;
        $distributor->distributor_type = $request->distributor_type ? $request->distributor_type : $distributor->distributor_type;
        $distributor->name = $request->name ? $request->name : $distributor->name;
        $distributor->phone = $request->phone ? $request->phone: $distributor->phone;
        $distributor->status = (string) $request->status;
        $distributor->save();
        
        if(isset($request->id)){
            return $this->successResponse('Updated Distributor Successfully', $distributor);
        }else{
            return $this->successResponse('Created Distributor Successfully', $distributor);
        }
    }
    
    public function getManagers(Request $request){
        $user = JWTAuth::user();
        if($user){
            $query=User::where('role_id', 6)->withCount(['managedEvents'])->orderBy('id','DESC');
            $total_record = User::where('role_id', 6)->count();

            $search = is_array($request->search) ? ($request->search['value'] ?? '') : $request->search;
            if ($search) {
                
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
                });
            }
            
            if(isset($request->is_inactive) && !empty($request->is_inactive)){
                if($request->is_inactive=='true'){
                    $query->where('status', '0');   
                }
            }
            
            $filtered_record=$query->count();
            $venues = [];
            $length = (int) $request->get('length', 20);
            $length = $length > 0 ? min($length, 50) : 20;
            $data=$query->offset((int) $request->get('start', 0))->limit($length)->get();
            
            if(count($data)>0){
                foreach($data as $key=>$val){
                    $name = trim(($val->first_name ?? '').' '.($val->last_name ?? '')) ?: $val->name;

                    $venues[]=array(
                        'id'         => $val->id,
                        'first_name' => $val->first_name,
                        'last_name'  => $val->last_name,
                        'name'       => $name,
                        'email'      => $val->email,
                        'phone'      => $val->phone,
                        'address'    => $val->address,
                        'status'     => (string) $val->status,
                        'linked_events_count' => (int) ($val->managed_events_count ?? 0),
                        'can_delete' => (int) ($val->managed_events_count ?? 0) === 0,
                        'action'     => $val->id
                    );
                }
            }
            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $venues,
                'recordsTotal'      => $total_record,
                'recordsFiltered'   => $filtered_record,
            ]);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function getManagerById(Request $request){
        $user = JWTAuth::user();
        if (!$request->has('id')) {
            return $this->errorResponse('Manager ID is required', 400);
        }
        $data = User::where('role_id', 6)->with('managerMarkets:id,name')->find($request->id);
        if (!$data) {
            return $this->errorResponse('Manager not found', 404);
        }
        return $this->successResponse('Manager data fetched successfully', $data);
    }
    
    public function addUpdateManager(Request $request){
        $user = JWTAuth::user();
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => isset($request->id) ? 'nullable|email' : 'required|email|unique:users,email',
            'phone' => 'required|string|max:15',
            'address' => 'required|string|max:255',
            'latitude' => isset($request->id) ? 'nullable' : 'required',
            'longitude' => isset($request->id) ? 'nullable' : 'required',
            'status' => 'required',
            'market_ids' => 'nullable|array',
            'market_ids.*' => 'exists:markets,id',
        ]);

        if(isset($request->id)){
            $manager = User::where('role_id', 6)->findOrFail($request->id);
        }else{
            $request->validate([
                'password' => 'required|string|min:8',
            ]);
            $manager = new User();
            $manager->email = $request->email;
            $manager->password = $request->password;
            $manager->role_id = $request->role_id ?? 6;
        }
        $manager->address = $request->address;
        $manager->first_name = $request->first_name;
        $manager->last_name = $request->last_name;
        $manager->name = $request->first_name.' '.$request->last_name;
        $manager->latitude = $request->latitude;
        $manager->phone = $request->phone;
        $manager->status = $request->status;
        $manager->latitude = $request->latitude;
        $manager->longitude = $request->longitude;
        $manager->save();
        $manager->managerMarkets()->sync($request->input('market_ids', []));
        $manager->load('managerMarkets');

        if(isset($request->id)){
            return $this->successResponse('Updated Manager Successfully', $manager);
        }else{
            return $this->successResponse('Created Manager Successfully', $manager);
        }
    }

    public function deleteManager(Request $request){
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        if ((int) $user->role_id !== 1) {
            return $this->errorResponse('You are not authorized to delete managers.', [], 403);
        }

        $Validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:users,id',
        ]);
        if ($Validator->fails()) {
            $errors = $Validator->errors();
            foreach ($errors->all() as $key => $value) {
                return $this->errorResponse(ucfirst($value), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
            }
        }

        $manager = User::where('role_id', 6)->find($request->id);
        if (! $manager) {
            return $this->errorResponse('Manager not found', 'ERROR', Response::HTTP_NOT_FOUND, new \stdClass());
        }

        $linkedEventsCount = Event::where('manager_id', $manager->id)->count();
        if ($linkedEventsCount > 0) {
            return $this->errorResponse(
                'This manager cannot be deleted because they are assigned to ' . $linkedEventsCount . ' event' . ($linkedEventsCount === 1 ? '' : 's') . '.',
                'ERROR',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                new \stdClass()
            );
        }

        $manager->managerMarkets()->detach();
        $manager->delete();

        return $this->successResponse('Manager deleted successfully.', []);
    }
    
    public function getPosUsers(Request $request){
        $user = JWTAuth::user();
        if($user){
            $query=User::where('role_id', 7)->orderBy('id','ASC');
            if ($request->search['value']) {
                $search = $request->search['value'];
                
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
                });
            }
            $total_record=$query->count();
            $venues = [];
            $data=$query->offset($request->start)->limit(10)->get();
            
            if(count($data)>0){
                foreach($data as $key=>$val){
                    $venues[]=array(
                        'name'    => $val->first_name.' '.$val->last_name,
                        'phone'         => $val->phone,
                        'action'        => '<ion-icon name="create"  size="large" color="success" id="'.$val->id.'"></ion-icon>'
                    );
                }
            }
            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $venues,
                'recordsTotal'      => $total_record,
                'recordsFiltered'   => $total_record,
            ]);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function addUpdatePosUser(Request $request){
        JWTAuth::user();

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required',
            'state' => 'required',
            'phone' => 'required|string|max:15',
            'latitude' => 'required',
            'longitude' => 'required',
            'market_ids' => 'required|array|min:1',
            'market_ids.*' => 'exists:markets,id',
        ]);

        if (isset($request->id)) {
            $posUser = User::where('role_id', 7)->findOrFail($request->id);
        } else {
            $request->validate([
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
            ]);
            $posUser = new User();
            $posUser->email = $request->email;
            $posUser->password = $request->password;
            $posUser->role_id = (int) ($request->role_id ?? 7);
        }

        $posUser->address = $request->address;
        $posUser->city = $request->city;
        $posUser->state = $request->state;
        $posUser->first_name = $request->first_name;
        $posUser->last_name = $request->last_name;
        $posUser->name = $request->first_name.' '.$request->last_name;
        $posUser->phone = $request->phone;
        $posUser->latitude = $request->latitude;
        $posUser->longitude = $request->longitude;
        $posUser->save();

        $posUser->posMarkets()->sync($request->market_ids);
        $posUser->load('posMarkets');

        if (isset($request->id)) {
            return $this->successResponse('Updated POS User Successfully', $posUser);
        }

        return $this->successResponse('Created POS User Successfully', $posUser);
    }
    
    public function getSettings(Request $request){
        $user = JWTAuth::user();
    
        $settings = Setting::whereIn('name', ['map', 'stripe', 'quickbook', 'dwolla_balance_alert'])
            ->pluck('value', 'name');
    
        return $this->successResponse('Settings Data Fetched Successfully', [
            'map' => json_decode($settings['map'] ?? '{}'),
            'stripe' => json_decode($settings['stripe'] ?? '{}'),
            'quickbook' => json_decode($settings['quickbook'] ?? '{}'),
            'dwolla_balance_alert' => json_decode($settings['dwolla_balance_alert'] ?? '{}'),
        ]);
    }
    
    public function addUpdateSettings(Request $request){
        $user = JWTAuth::user();
        $allowedSettings = ['quickbook', 'stripe', 'map', 'dwolla_balance_alert'];
        $updated = [];
        foreach ($allowedSettings as $settingName) {
            if ($request->has($settingName)) {
                $data = Setting::updateOrCreate(
                    ['name' => $settingName],
                    ['value' => $request->input($settingName)]
                );
    
                $updated[$settingName] = $data;
            }
        }
        if (empty($updated)) {
            return $this->errorResponse('No valid settings provided.', 400);
        }
        return $this->successResponse('Settings updated successfully.', $updated);
    }

    public function quickbooksSetup(Request $request){
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        return $this->successResponse('QuickBooks setup fetched successfully.', $this->quickbooksSetupPayload());
    }

    public function quickbooksSetupAction(Request $request, QuickBooksService $quickBooksService){
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|string|in:accounts,terms,payment_methods,expense_categories,services',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
        }

        if (! Cache::has('qb_access_token') || ! Cache::has('qb_realm_id')) {
            return $this->errorResponse('QuickBooks is not connected. Please connect first.', 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
        }

        try {
            $result = ['success' => true, 'message' => 'QuickBooks setup updated.'];

            switch ($request->action) {
                case 'accounts':
                    $result = $quickBooksService->syncChartOfAccounts();
                    break;
                case 'terms':
                    $result = $quickBooksService->syncTerms();
                    break;
                case 'payment_methods':
                    $result = $quickBooksService->syncPaymentMethodsFromQuickBooks();
                    break;
                case 'expense_categories':
                    $quickBooksService->ensureDefaultExpenseCategory();
                    $result = $this->syncQuickBooksExpenseCategories($quickBooksService);
                    break;
                case 'services':
                    $result = $this->syncQuickBooksServices($quickBooksService);
                    break;
            }

            if (! ($result['success'] ?? false)) {
                return $this->errorResponse($result['message'] ?? 'QuickBooks sync failed.', 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, $this->quickbooksSetupPayload());
            }

            return $this->successResponse($result['message'] ?? 'QuickBooks sync completed.', $this->quickbooksSetupPayload());
        } catch (\Throwable $e) {
            Log::error('QuickBooks setup action failed', [
                'action' => $request->action,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('QuickBooks sync failed: '.$e->getMessage(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, $this->quickbooksSetupPayload());
        }
    }

    private function quickbooksSetupPayload(): array
    {
        app(ServiceController::class)->ensureSystemServicesExistPublic();

        $quickbooksConnected = Cache::has('qb_access_token') && Cache::has('qb_realm_id');
        $qbRealmId = Cache::get('qb_realm_id');

        $totalAccounts = QuickBooksAccount::count();
        $lastAccountSync = QuickBooksAccount::latest('updated_at')->value('updated_at');
        $accountsSynced = $totalAccounts > 0;

        $totalTerms = QuickBooksTerm::count();
        $lastTermSync = QuickBooksTerm::latest('updated_at')->value('updated_at');
        $termsSynced = $totalTerms > 0;

        $totalPaymentMethods = PaymentMethod::where('active', true)->count();
        $lastPaymentMethodSync = PaymentMethod::where('active', true)->latest('updated_at')->value('updated_at');
        $paymentMethodsSynced = $totalPaymentMethods > 0;

        app(QuickBooksService::class)->ensureDefaultExpenseCategory();
        $allExpenseCategories = ExpenseCategory::orderByDesc('is_default')->orderBy('name')->get();
        $totalExpenseCategories = $allExpenseCategories->count();
        $syncedExpenseCategories = $allExpenseCategories->whereNotNull('quickbooks_account_id')->count();
        $pendingExpenseCategories = $allExpenseCategories->whereNull('quickbooks_account_id')->count();
        $lastExpenseCategorySync = ExpenseCategory::whereNotNull('quickbooks_account_id')->latest('updated_at')->value('updated_at');
        $expenseCategoriesSynced = $syncedExpenseCategories > 0;

        $mandatoryNames = array_map('mb_strtolower', array_keys(ServiceController::SYSTEM_SERVICES));
        $allActiveServices = Service::where('is_active', true)->get();
        $mandatoryServices = $allActiveServices->filter(fn ($service) => in_array(mb_strtolower($service->name), $mandatoryNames, true))->values();
        $customServices = $allActiveServices->reject(fn ($service) => in_array(mb_strtolower($service->name), $mandatoryNames, true))->values();
        $totalServices = $allActiveServices->count();
        $syncedServices = $allActiveServices->whereNotNull('qb_item_id')->count();
        $pendingServices = $allActiveServices->whereNull('qb_item_id')->count();
        $mandatoryAllSynced = $mandatoryServices->count() >= count($mandatoryNames)
            && $mandatoryServices->whereNull('qb_item_id')->isEmpty();

        $qbSetupReady = $quickbooksConnected
            && $accountsSynced
            && $termsSynced
            && $paymentMethodsSynced
            && $expenseCategoriesSynced
            && $mandatoryAllSynced;
        $allDone = $qbSetupReady;

        $steps = [
            [
                'key' => 'connection',
                'label' => 'QuickBooks Connection',
                'description' => $quickbooksConnected ? 'Your QuickBooks account is connected and active.' : 'Connect your QuickBooks account to enable sync and invoice generation.',
                'done' => $quickbooksConnected,
                'action' => 'connect',
                'button' => $quickbooksConnected ? 'Reconnect' : 'Connect to QuickBooks',
                'stats' => [
                    ['label' => 'Status', 'value' => $quickbooksConnected ? 'Connected' : 'Not Connected'],
                    ['label' => 'Realm ID', 'value' => $qbRealmId ?: '-'],
                ],
            ],
            [
                'key' => 'accounts',
                'label' => 'Chart of Accounts',
                'description' => 'Required for Services, Expenses, Invoice generation and Payments.',
                'done' => $accountsSynced,
                'action' => 'accounts',
                'button' => 'Sync Chart of Accounts',
                'stats' => [
                    ['label' => 'Accounts', 'value' => $totalAccounts],
                    ['label' => 'Last Sync', 'value' => $this->formatQuickbooksSyncDate($lastAccountSync)],
                ],
            ],
            [
                'key' => 'terms',
                'label' => 'Payment Terms',
                'description' => 'Payment Terms keep QuickBooks Term IDs up to date after reconnecting or resetting your company.',
                'done' => $termsSynced,
                'action' => 'terms',
                'button' => 'Sync Terms',
                'stats' => [
                    ['label' => 'Terms', 'value' => $totalTerms],
                    ['label' => 'Last Sync', 'value' => $this->formatQuickbooksSyncDate($lastTermSync)],
                ],
            ],
            [
                'key' => 'payment_methods',
                'label' => 'Payment Methods',
                'description' => 'Sync available QuickBooks Payment Methods required for Ambassador Payout and Reimbursement expense creation.',
                'done' => $paymentMethodsSynced,
                'action' => 'payment_methods',
                'button' => 'Sync Payment Methods',
                'stats' => [
                    ['label' => 'Methods', 'value' => $totalPaymentMethods],
                    ['label' => 'Last Sync', 'value' => $this->formatQuickbooksSyncDate($lastPaymentMethodSync)],
                ],
            ],
            [
                'key' => 'expense_categories',
                'label' => 'Expense Categories',
                'description' => 'Sync Expense Categories used for Ambassador Payouts and Reimbursements.',
                'done' => $expenseCategoriesSynced,
                'action' => 'expense_categories',
                'button' => 'Sync Expense Categories',
                'stats' => [
                    ['label' => 'Total', 'value' => $totalExpenseCategories],
                    ['label' => 'Synced', 'value' => $syncedExpenseCategories],
                    ['label' => 'Pending', 'value' => $pendingExpenseCategories],
                    ['label' => 'Last Sync', 'value' => $this->formatQuickbooksSyncDate($lastExpenseCategorySync)],
                ],
                'items' => $allExpenseCategories->map(fn ($category) => [
                    'name' => $category->name,
                    'done' => ! empty($category->quickbooks_account_id),
                    'is_default' => (bool) ($category->is_default ?? false),
                ])->values(),
            ],
            [
                'key' => 'services',
                'label' => 'Services',
                'description' => $mandatoryAllSynced ? 'Required invoice services are synced and ready.' : 'All 7 mandatory system services must be synced before creating invoices.',
                'done' => $mandatoryAllSynced,
                'action' => 'services',
                'button' => 'Sync Services to QuickBooks',
                'stats' => [
                    ['label' => 'Total', 'value' => $totalServices],
                    ['label' => 'Synced', 'value' => $syncedServices],
                    ['label' => 'Pending', 'value' => $pendingServices],
                ],
                'items' => [
                    'mandatory' => $mandatoryServices->map(fn ($service) => [
                        'name' => $service->name,
                        'done' => ! empty($service->qb_item_id),
                    ])->values(),
                    'custom' => $customServices->map(fn ($service) => [
                        'name' => $service->name,
                        'done' => ! empty($service->qb_item_id),
                    ])->values(),
                ],
            ],
            [
                'key' => 'ready',
                'label' => 'Ready for Invoice & Expense Processing',
                'description' => $qbSetupReady ? 'You are all set to create and publish invoices and process expenses in QuickBooks.' : 'Complete the steps above to unlock invoice creation and expense processing.',
                'done' => $qbSetupReady,
                'action' => null,
                'button' => null,
                'stats' => [],
            ],
        ];

        $completedSteps = collect($steps)->where('done', true)->count();
        $totalSteps = count($steps);
        $progressPct = (int) round(($completedSteps / max($totalSteps, 1)) * 100);
        $nextStep = collect($steps)->first(fn ($step) => ! $step['done']);

        return [
            'connected' => $quickbooksConnected,
            'realm_id' => $qbRealmId,
            'auth_url' => $this->quickbooksAuthUrl(),
            'progress' => [
                'completed' => $completedSteps,
                'total' => $totalSteps,
                'percent' => $progressPct,
                'all_done' => $allDone,
                'next_step' => $nextStep,
            ],
            'steps' => $steps,
        ];
    }

    private function syncQuickBooksServices(QuickBooksService $quickBooksService): array
    {
        app(ServiceController::class)->ensureSystemServicesExistPublic();
        $activeServices = Service::where('is_active', true)->get();
        $unsyncedServices = $activeServices->whereNull('qb_item_id');

        if ($unsyncedServices->isEmpty()) {
            return ['success' => true, 'message' => 'All services are already synced with QuickBooks.', 'synced' => 0];
        }

        $synced = 0;
        $failed = 0;

        foreach ($unsyncedServices as $service) {
            $qbItem = $quickBooksService->createQuickBooksService([
                'name' => $service->name,
                'description' => $service->description ?? '',
                'rate' => null,
                'taxable' => false,
            ]);

            if ($qbItem) {
                $service->update([
                    'qb_item_id' => $qbItem->Id,
                    'sync_token' => $qbItem->SyncToken,
                ]);
                $synced++;
            } else {
                $failed++;
            }
        }

        if ($failed > 0) {
            return ['success' => false, 'message' => "Sync completed: {$synced} synced, {$failed} failed.", 'synced' => $synced, 'failed' => $failed];
        }

        return ['success' => true, 'message' => "{$synced} service(s) synced to QuickBooks successfully.", 'synced' => $synced];
    }

    private function syncQuickBooksExpenseCategories(QuickBooksService $quickBooksService): array
    {
        $categories = ExpenseCategory::whereNull('quickbooks_account_id')->get();
        $synced = 0;
        $failed = 0;

        foreach ($categories as $category) {
            $qbAccountId = $quickBooksService->ensureNamedExpenseAccount($category->name, $this->quickbooksExpenseDetailTypeCode($category->detail_type));
            if ($qbAccountId) {
                $category->update(['quickbooks_account_id' => $qbAccountId]);
                $synced++;
            } else {
                $failed++;
            }
        }

        $alreadySynced = ExpenseCategory::whereNotNull('quickbooks_account_id')->count();
        $message = "Expense Categories synced: {$synced} new";
        if ($alreadySynced > 0) {
            $message .= ", {$alreadySynced} already synced";
        }

        if ($failed > 0) {
            return ['success' => false, 'message' => "{$failed} category(ies) failed to sync. {$message}."];
        }

        return ['success' => true, 'message' => $message.'.'];
    }

    private function quickbooksExpenseDetailTypeCode(?string $label): ?string
    {
        $map = [
            'Advertising/Promotional' => 'AdvertisingPromotional',
            'Automobile' => 'Automobile',
            'Bad Debts' => 'BadDebts',
            'Bank Charges' => 'BankCharges',
            'Charitable Contributions' => 'CharitableContributions',
            'Cost of Labor' => 'CostOfLabor',
            'Dues & subscriptions' => 'DuesSubscriptions',
            'Entertainment' => 'Entertainment',
            'Insurance' => 'Insurance',
            'Legal & Professional Fees' => 'LegalProfessionalFees',
            'Meals & Entertainment' => 'MealsAndEntertainment',
            'Office/General Administrative Expenses' => 'OfficeExpenses',
            'Other Business Expenses' => 'OtherBusinessExpenses',
            'Other Miscellaneous Expense' => 'OtherMiscellaneousExpense',
            'Payroll Expenses' => 'PayrollExpenses',
            'Rent or Lease of Buildings' => 'RentOrLease',
            'Repair & Maintenance' => 'RepairsMaintenance',
            'Shipping, Freight & Delivery' => 'ShippingFreightDelivery',
            'Supplies & Materials' => 'SuppliesMaterials',
            'Taxes Paid' => 'TaxesPaid',
            'Travel' => 'Travel',
            'Utilities' => 'Utilities',
        ];

        return $map[$label] ?? $label;
    }

    private function formatQuickbooksSyncDate($value): string
    {
        return $value ? Carbon::parse($value)->diffForHumans() : '-';
    }

    private function quickbooksAuthUrl(): ?string
    {
        try {
            $config = config('quickbooks');
            $dataService = DataService::Configure([
                'auth_mode' => 'oauth2',
                'ClientID' => $config['client_id'],
                'ClientSecret' => $config['client_secret'],
                'RedirectURI' => $config['redirect_uri'] ?? '',
                'scope' => $config['scope'],
                'baseUrl' => $config['base_url'],
            ]);

            return $dataService->getOAuth2LoginHelper()->getAuthorizationCodeURL();
        } catch (\Throwable $e) {
            Log::warning('Unable to generate QuickBooks auth URL for API settings.', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    public function getUnapproveAmbassadorList(Request $request) {
        $user = JWTAuth::user();
        if($user){
            $query=AssignAmbassador::with(['event.location','event.brand','ambassador'])->where('status','0')->orderByDesc('id');
            $total_record=$query->count();
            $venues = [];
            $length = (int) $request->get('length', 20);
            $length = $length > 0 ? min($length, 50) : 20;
            $data=$query->offset((int) $request->get('start', 0))->limit($length)->get();

            if(count($data)>0){
                foreach($data as $key=>$val){
                    $venues[]=array(
                        'id'               => $val->id,
                        'event_id'         => $val->event_id,
                        'ambassador_id'    => $val->ambassador_id,
                        'event'            => $val->event ? $val->event->event_name : '-',
                        'event_name'       => $val->event ? $val->event->event_name : '-',
                        'event_date'       => $val->event?->formatted_date ?? $val->event?->start_time,
                        'event_time'       => $val->event?->formatted_time ?? null,
                        'location'         => $val->event?->location?->name ?? $val->event?->city ?? '-',
                        'brand'            => $val->event?->brand?->brand_name ?? '-',
                        'name'             => $val->ambassador?->name ?? '-',
                        'ambassador_name'  => $val->ambassador?->name ?? '-',
                        'email'            => $val->ambassador?->email,
                        'phone'            => $val->ambassador?->phone,
                        'status'           => (string) $val->status,
                        'action'           => $val->id
                    );
                }
            }
            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $venues,
                'recordsTotal'      => $total_record,
                'recordsFiltered'   => $total_record,
            ]);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function getEventsData(Request $request){
        $user = JWTAuth::user();
        if ($user) {
            $this->eventService->markEndedEventsCompletedForApi();
        }
        
        $states = EventLocation::orderBy('state')->get();
        $locations = User::select('id', 'name')->where('role_id', 4)->whereNotNull('name')->orderBy('name')->get();
        $clients = User::where('role_id', 3)->get();
        $venueTypes = Venue::get();
        $departments = EventDepartment::get();
        $distributors = EventDistributor::get();
        $brands = Brand::get();
        $managers = User::where('role_id', '6')->where('status', '1')->get();
        $markets = $this->marketsForUser($user);
        $eventTypes = EventType::get();
        $filterMetadata = $this->eventFilterMetadata($user, $request);
        $booked_user = User::select('id','name','email')->whereIn('role_id', [1,5,6])->get();
        return $this->successResponse('Events data fetched successfully.', 
        array_merge([
            'locations'    => $locations,
            'clients'      => $clients,
            'departments'  => $departments,
            'venueTypes'   => $venueTypes,
            'distributors' => $distributors,
            'brands'       => $brands,
            'managers'     => $managers,
            'markets'      => $markets,
            'eventTypes'   => $eventTypes,
            'states'       => $states,
            'booked_user'  => $booked_user,
        ], $filterMetadata));
    }
    
    public function searchAmbassador(Request $request){
        $query = User::where('role_id', 5);
        $search = $request->search;
        $ageRestricted = filter_var($request->age_restricted, FILTER_VALIDATE_BOOLEAN);
    
        // Filter by name/email
        $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%$search%")
                ->orWhere('last_name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%");
        });
    
        // If age restricted, filter users >= 21 years old
        if ($ageRestricted) {
            $query->whereNotNull('dob')
                  ->whereDate('dob', '<=', now()->subYears(21)->format('Y-m-d'));
        }
        $ambassadors = $query->get();
    
        return $this->successResponse('Ambassadors found.', $ambassadors);
    }
    
    public function addEvent(Request $request) {
        $request->merge(['booked_by' => $this->normalizeBookedByEmails($request->input('booked_by'))]);

        $validated = $request->validate([
            'event_name' => 'nullable|string|max:255',
            'event_type_id' => 'required|exists:event_types,id',
            'event_location_id' => 'required|exists:event_locations,id',
            'venue_type_id' => 'nullable|exists:venues,id',
            'client_id' => 'required_without:billing_party_ids|nullable|exists:users,id',
            'billing_party_ids' => 'nullable|array',
            'billing_party_ids.*' => 'exists:users,id',
            'primary_billing_party_id' => 'nullable|exists:users,id',
            'department_id' => 'required|exists:event_departments,id',
            'position' => 'required|integer|min:1',
            // 'age_restricted' => 'boolean',
            // 'recap_needed' => 'boolean',
            'distributor_id' => 'required|exists:event_distributors,id',
            'brand_id' => 'required|exists:brands,id',
            'market_ids' => 'required_without:market_id|array|min:1',
            'market_ids.*' => 'exists:markets,id',
            'market_id' => 'required_without:market_ids|exists:markets,id',
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
            'labor_rates' => 'nullable|array',
            'labor_rates.*' => 'nullable|numeric|min:0',
            'buyback_amount' => 'nullable|numeric|min:0',
            'cups_amount' => 'nullable|numeric|min:0',
            'split_cups' => 'nullable',
            'city' => 'nullable|string|max:255',
            'event_address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            // 'payment_status' => 'boolean',
            // 'receipt' => 'boolean',
            // 'kit' => 'boolean',
            // 'kit_description' => 'string',
            // 'pinata_pay' => 'boolean',
            // 'integrate_with_best' => 'boolean',
        ], [
            'event_type_id.required' => 'Event Type is required.',
            'event_type_id.exists' => 'The selected Event Type is invalid.',
            'booked_by.required' => 'Booked By is required.',
            'booked_by.*.email' => 'Each Booked By entry must be a valid email address.',
        ]);

        $validated['booked_by'] = json_encode($request->input('booked_by'));

        $jwtUser = JWTAuth::user();

        $marketIds = collect($request->input('market_ids', []));
        if ($marketIds->isEmpty() && $request->filled('market_id')) {
            $marketIds = collect([(int) $request->market_id]);
        }
        $marketIds = $marketIds->map(fn ($id) => (int) $id)->unique()->values();

        if ($jwtUser && (int) $jwtUser->role_id === 7) {
            $allowed = $this->posUserAllowedMarketIds($jwtUser) ?? [];
            $diff = $marketIds->diff($allowed);
            if ($diff->isNotEmpty()) {
                return $this->errorResponse('You can only work with events in your assigned markets.', 'forbidden', Response::HTTP_FORBIDDEN, new \stdClass());
            }
        }
    
        $validated['apply_event'] = $this->requestBoolean($request, 'apply_event') ? 1 : 0;
        // events.ambassador_id is a legacy non-null column. New events may be
        // unstaffed, so persist an empty CSV value instead of NULL.
        $validated['ambassador_id'] = $request->selectedAmbassadorIds ?: '';
        $validated['age_restricted'] = $this->requestBoolean($request, 'age_restricted') ? 1 : 0;
        $validated['recap_needed'] = $this->requestBoolean($request, 'recap_needed') ? 1 : 0;
        $validated['payment_status'] = $this->requestBoolean($request, 'payment_status') ? 1 : 0;
        $validated['integrate_with_best'] = $this->requestBoolean($request, 'integrate_with_best') ? 1 : 0;
        $validated['kit'] = $this->requestBoolean($request, 'kit') ? 1 : 0;
        $validated['pinata_pay'] = $this->requestBoolean($request, 'pinata_pay') ? 1 : 0;
        $validated['receipt'] = $this->requestBoolean($request, 'receipt') ? 1 : 0;
        $validated['split_cups'] = $this->requestBoolean($request, 'split_cups') ? 1 : 0;
        $validated['buyback_amount'] = $request->filled('buyback_amount') ? $request->buyback_amount : 0;
        $validated['cups_amount'] = $request->filled('cups_amount') ? $request->cups_amount : 0;

        $billingPartyIds = $this->integerArrayInput($request, 'billing_party_ids');
        if (! empty($billingPartyIds)) {
            $primaryId = (int) ($request->input('primary_billing_party_id') ?: $billingPartyIds[0]);
            $validated['client_id'] = in_array($primaryId, $billingPartyIds, true) ? $primaryId : $billingPartyIds[0];
        }

        $locationService = app(EventLocationService::class);
        $validated = $locationService->applyEventLocationFromVenue($validated);
        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            return $this->errorResponse(
                'Selected venue is missing coordinates. Update the venue address first.',
                'validation_error',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                new \stdClass()
            );
        }

        // Keep legacy events.market_id in sync (first selected) for compatibility.
        $validated['market_id'] = $marketIds->first();
        unset($validated['billing_party_ids'], $validated['primary_billing_party_id'], $validated['market_ids'], $validated['labor_rates']);

        if ($request->id) {
            // Find the existing event by id
            $event = Event::find($request->id);
            if (!$event) {
                return $this->errorResponse('Event not found', 'not_found', 404);
            }

            if (! isset($validated['event_name']) || trim((string) $validated['event_name']) === '') {
                $validated['event_name'] = $this->generateEventName($event->id);
            }

            // Update the event with validated data
            $event->update($validated);
            $event->markets()->sync($marketIds->all());
            $this->syncApiEventBillingParties($event, $request);
            $event->labor_amount = $this->calculateApiLaborAmount($event);
            $event->save();
    
            // Set a custom success response message for update
            $message = 'EVENT ID '.$event->id.' SAVED';
        } else {
            $requestedEventName = trim((string) ($validated['event_name'] ?? ''));
            $autoEventName = trim((string) $request->input('auto_event_name', ''));
            $shouldAutoGenerateEventName = $requestedEventName === '' || ($autoEventName !== '' && $requestedEventName === $autoEventName);
            if ($shouldAutoGenerateEventName) {
                $validated['event_name'] = $this->generateEventNameFromNextId();
            }

            // Create a new event
            $event = Event::create($validated);
            if ($shouldAutoGenerateEventName) {
                $event->event_name = $this->generateEventName($event->id);
                $event->save();
            }
            $event->markets()->sync($marketIds->all());
            $this->syncApiEventBillingParties($event, $request);
            $event->labor_amount = $this->calculateApiLaborAmount($event);
            $event->save();
    
            // Set a custom success response message for creation
            $message = 'EVENT ID '.$event->id.' created';
        }
        
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $mimeType = $file->getMimeType();
                $destination = public_path('attachments');
                if (!file_exists($destination)) {
                    mkdir($destination, 0755, true);
                }
    
                $file->move($destination, $filename);
    
                $pathName = 'attachments/' . $filename;
    
                $attachment = new Attachement();
                $attachment->event_id = $event->id;
                $attachment->filename = $pathName;
                $attachment->driver = 1;
                $attachment->type = $mimeType;
                $attachment->save();
            }
        }
    
        if (!empty($request->selectedAmbassadorIds)) {
            $ambassadorId = explode(',', $request->selectedAmbassadorIds);
            $overridePayData = json_decode($request->input('overridePayData'), true);
    
            foreach ($ambassadorId as $id) {
                $overridePay = $overridePayData[$id] ?? 0;
                if (empty($overridePay) || !is_numeric($overridePay)) {
                    $overridePay = 0;
                }
                $overridePay = (int) $overridePay;
    
                $assign = new AssignAmbassador();
                $assign->event_id = $event->id;
                $assign->ambassador_id = $id;
                $assign->status = '1';
                $assign->override_pay = $overridePay;
                $assign->pinata_amount = !empty($validated['pinata_pay']) ? 5 : 0;
                $assign->save();
            }
        }
        return $this->successResponse($message, $event->load('markets'));
    }

    private function requestBoolean(Request $request, string $key): bool
    {
        if (! $request->has($key)) {
            return false;
        }

        return filter_var($request->input($key), FILTER_VALIDATE_BOOLEAN);
    }

    private function integerArrayInput(Request $request, string $key): array
    {
        $value = $request->input($key, []);
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : explode(',', $value);
        }
        if (! is_array($value)) {
            $value = [$value];
        }

        return collect($value)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function syncApiEventBillingParties(Event $event, Request $request): void
    {
        $ids = $this->integerArrayInput($request, 'billing_party_ids');
        if (empty($ids)) {
            EventBillingParty::where('event_id', $event->id)->delete();
            return;
        }

        $primaryId = (int) ($request->input('primary_billing_party_id') ?: $ids[0]);
        $primaryId = in_array($primaryId, $ids, true) ? $primaryId : $ids[0];
        $laborRates = $request->input('labor_rates', []);
        if (is_string($laborRates)) {
            $decoded = json_decode($laborRates, true);
            $laborRates = is_array($decoded) ? $decoded : [];
        }

        EventBillingParty::where('event_id', $event->id)->delete();

        foreach (array_values($ids) as $sortOrder => $clientId) {
            $laborRate = null;
            if (is_array($laborRates) && isset($laborRates[$clientId]) && $laborRates[$clientId] !== '') {
                $laborRate = (float) $laborRates[$clientId];
            } else {
                $client = User::find($clientId);
                if ($client && $client->default_labor_amount !== null) {
                    $laborRate = (float) $client->default_labor_amount;
                }
            }

            EventBillingParty::create([
                'event_id' => $event->id,
                'client_id' => $clientId,
                'is_primary' => $clientId === $primaryId,
                'sort_order' => $sortOrder,
                'labor_rate' => $laborRate,
            ]);
        }
    }

    private function calculateApiLaborAmount(Event $event): float
    {
        $start = Carbon::parse($event->start_time);
        $end = Carbon::parse($event->end_time);
        $durationHours = max($start->diffInMinutes($end) / 60, 0);
        $parties = $event->billingParties()->get();

        if ($parties->isEmpty()) {
            $client = $event->client_id ? User::find($event->client_id) : null;
            $rate = (float) ($client->default_labor_amount ?? 0);
            return round($durationHours * $rate, 2);
        }

        $total = max($parties->count(), 1);
        $labor = 0;
        foreach ($parties as $party) {
            $rate = (float) ($party->labor_rate ?? 0);
            $labor += ($durationHours * $rate) / $total;
        }

        return round($labor, 2);
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


   
    public function assignAmbassaborEvent(Request $request) {
        $user = JWTAuth::user();
        if($user){
            $id = $request->id;
            $event = AssignAmbassador::find($id);
            if(!$event){
                return $this->errorResponse('Event is not exist', 'not_found', 403);
            }
            app(AmbassadorStaffingService::class)->approveAssignment($event, true);

            return $this->successResponse('Ambassador assigned to event successfully.', ['assigned' => true]);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }

    public function rejectAmbassaborEvent(Request $request) {
        $user = JWTAuth::user();
        if($user){
            $id = $request->id;
            $event = AssignAmbassador::where('status', '0')->find($id);
            if(!$event){
                return $this->errorResponse('Pending ambassador request is not exist', 'not_found', 403);
            }

            $event->delete();

            return $this->successResponse('Ambassador request rejected successfully.', ['rejected' => true]);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function getPayForecast(Request $request) {
        $locations = EventLocation::select('id', 'address as name', 'address')->orderBy('address')->get();
        $markets = Market::query()->active()->ordered()->get();
        $brands = Brand::get();
        $managers = User::where('role_id', 6)->where('status', '1')->get();
        $ambassadors = User::select('id', 'name', 'email')->where('role_id', 5)->orderBy('name')->get();

        $query = Recap::query()
            ->select([
                'recaps.*',
                'aa.override_pay as assignment_override_pay',
                'aa.pinata_amount as assignment_pinata_amount',
                'aa.event_date_time as assignment_event_date_time',
                'aa.is_no_show as assignment_is_no_show',
            ])
            ->leftJoin('assign_ambassadors as aa', function ($join) {
                $join->on('aa.event_id', '=', 'recaps.event_id')
                    ->on('aa.ambassador_id', '=', 'recaps.ambassador_id');
            })
            ->where('recaps.is_draft', false)
            ->whereHas('event', function ($q) {
                $q->where('event_status', '!=', '2');
            })
            ->whereNotExists(function ($sub) {
                $sub->from('ambassador_payouts')
                    ->whereColumn('ambassador_payouts.event_id', 'recaps.event_id')
                    ->whereColumn('ambassador_payouts.user_id', 'recaps.ambassador_id')
                    ->whereIn('ambassador_payouts.status', ['completed', 'paid']);
            })
            ->whereNotExists(function ($sub) {
                $sub->from('payroll_batch_items')
                    ->whereColumn('payroll_batch_items.reference_id', 'recaps.id')
                    ->where('payroll_batch_items.payment_type', 'recap')
                    ->where('payroll_batch_items.status', 'completed');
            });

        if ($request->filled('market_id')) {
            $query->whereHas('event', fn ($q) => $q->where('market_id', $request->market_id));
        }

        if ($request->filled('manager_id')) {
            $query->whereHas('event', fn ($q) => $q->where('manager_id', $request->manager_id));
        }

        if ($request->filled('location')) {
            $query->whereHas('event', fn ($q) => $q->where('event_location_id', $request->location));
        }

        if ($request->filled('ambassador_name')) {
            $query->where('recaps.ambassador_id', $request->ambassador_name);
        }

        if ($request->filled('ambassador_email')) {
            $query->where('recaps.ambassador_id', $request->ambassador_email);
        }

        if ($request->filled('brand_id')) {
            $query->whereHas('event', fn ($q) => $q->forBrandOrDescendants($request->brand_id));
        }

        if ($request->filled('year')) {
            $query->whereHas('event', fn ($q) => $q->whereYear('start_time', $request->year));
        }

        if ($request->filled('month')) {
            $query->whereHas('event', fn ($q) => $q->whereMonth('start_time', $request->month));
        }

        $startDate = $request->start_day ?: $request->start_date;
        $endDate = $request->end_day ?: $request->end_date;

        if ($startDate && $endDate) {
            $query->whereHas('event', fn ($q) => $q->whereBetween(
                DB::raw('DATE(start_time)'),
                [date('Y-m-d', strtotime($startDate)), date('Y-m-d', strtotime($endDate))]
            ));
        } elseif ($startDate) {
            $query->whereHas('event', fn ($q) => $q->whereDate('start_time', '>=', date('Y-m-d', strtotime($startDate))));
        } elseif ($endDate) {
            $query->whereHas('event', fn ($q) => $q->whereDate('start_time', '<=', date('Y-m-d', strtotime($endDate))));
        }

        if ($request->has('pinata_pay') && $request->pinata_pay == 1) {
            $query->whereHas('event', fn ($q) => $q->where('pinata_pay', 1));
        }

        if ($request->has('no_show') && $request->no_show == 1) {
            $query->where('aa.is_no_show', 1);
        }

        $recaps = $query
            ->with(['event.brand', 'event.location', 'event.manager', 'ambassador'])
            ->latest('recaps.id')
            ->get();

        $reimbursements = $this->buildForecastReimbursementQuery($request)->get();
        $rows = $this->buildForecastRows($recaps, $reimbursements);

        $perPage = max(1, min((int) $request->input('per_page', $request->input('length', 20)), 50));
        $page = max(1, (int) $request->input('page', floor(((int) $request->input('start', 0)) / $perPage) + 1));
        $total = $rows->count();
        $pagedRows = $rows->forPage($page, $perPage)->values();

        return $this->successResponse('Data fetched successfully', [
            "draw"              => (int)$request->draw,
            'data'              => $pagedRows,
            'rows'              => $pagedRows,
            'recordsTotal'      => $total,
            'recordsFiltered'   => $total,
            'total'             => $total,
            'current_page'      => $page,
            'per_page'          => $perPage,
            'last_page'         => (int) ceil($total / $perPage),
            'payment_types'     => ['Event Pay', 'Event Pay (Expedited)', 'Reimbursement'],
            'locations'         => $locations,
            'markets'           => $markets,
            'brands'            => $brands,
            'ambassadors'       => $ambassadors,
            'managers'          => $managers
        ]);
    }

    private function buildForecastReimbursementQuery(Request $request)
    {
        $query = Reimbursement::query()
            ->with(['user', 'event.brand', 'event.location', 'event.manager', 'brand'])
            ->whereIn('status', ['pending', 'approved']);

        if ($request->filled('market_id')) {
            $query->whereHas('event', fn ($q) => $q->where('market_id', $request->market_id));
        }

        if ($request->filled('manager_id')) {
            $query->whereHas('event', fn ($q) => $q->where('manager_id', $request->manager_id));
        }

        if ($request->filled('location')) {
            $query->whereHas('event', fn ($q) => $q->where('event_location_id', $request->location));
        }

        if ($request->filled('ambassador_name')) {
            $query->where('user_id', $request->ambassador_name);
        }

        if ($request->filled('ambassador_email')) {
            $query->where('user_id', $request->ambassador_email);
        }

        if ($request->filled('brand_id')) {
            $brandIds = Brand::descendantIdsIncludingSelfById((int) $request->brand_id);
            $query->where(function ($q) use ($brandIds, $request) {
                $q->whereIn('brand_id', $brandIds)
                    ->orWhereHas('event', fn ($eventQuery) => $eventQuery->forBrandOrDescendants($request->brand_id));
            });
        }

        if ($request->filled('year')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('event', fn ($eventQuery) => $eventQuery->whereYear('start_time', $request->year))
                    ->orWhereYear('expense_date', $request->year);
            });
        }

        if ($request->filled('month')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('event', fn ($eventQuery) => $eventQuery->whereMonth('start_time', $request->month))
                    ->orWhereMonth('expense_date', $request->month);
            });
        }

        $startDate = $request->start_day ?: $request->start_date;
        $endDate = $request->end_day ?: $request->end_date;

        if ($startDate && $endDate) {
            $query->where(function ($q) use ($startDate, $endDate) {
                $range = [date('Y-m-d', strtotime($startDate)), date('Y-m-d', strtotime($endDate))];
                $q->whereHas('event', fn ($eventQuery) => $eventQuery->whereBetween(DB::raw('DATE(start_time)'), $range))
                    ->orWhereBetween('expense_date', $range);
            });
        } elseif ($startDate) {
            $query->where(function ($q) use ($startDate) {
                $date = date('Y-m-d', strtotime($startDate));
                $q->whereHas('event', fn ($eventQuery) => $eventQuery->whereDate('start_time', '>=', $date))
                    ->orWhereDate('expense_date', '>=', $date);
            });
        } elseif ($endDate) {
            $query->where(function ($q) use ($endDate) {
                $date = date('Y-m-d', strtotime($endDate));
                $q->whereHas('event', fn ($eventQuery) => $eventQuery->whereDate('start_time', '<=', $date))
                    ->orWhereDate('expense_date', '<=', $date);
            });
        }

        if ($request->filled('pinata_pay') && $request->pinata_pay == 1) {
            $query->whereRaw('1 = 0');
        }

        if ($request->filled('no_show') && $request->no_show == 1) {
            $query->whereRaw('1 = 0');
        }

        return $query->latest('id');
    }

    private function buildForecastRows($recaps, $reimbursements)
    {
        $batchItems = PayrollBatchItem::query()
            ->where('payment_type', 'recap')
            ->whereIn('reference_id', $recaps->pluck('id')->all())
            ->latest('id')
            ->get()
            ->unique('reference_id')
            ->keyBy('reference_id');

        $payouts = AmbassadorPayout::query()
            ->whereIn('event_id', $recaps->pluck('event_id')->all())
            ->whereIn('user_id', $recaps->pluck('ambassador_id')->all())
            ->get()
            ->keyBy(fn ($payout) => $payout->event_id . ':' . $payout->user_id);

        $recapRows = $recaps->map(function ($recap) use ($batchItems, $payouts) {
            $payout = $payouts->get($recap->event_id . ':' . $recap->ambassador_id);
            $batchItem = $batchItems->get($recap->id);
            $grossAmount = (float) ($payout?->original_amount ?? $this->calculateForecastRecapGrossAmount($recap));
            $fee = (float) ($payout?->expedited_fee ?? ($recap->is_expedited_requested ? 2.50 : 0.00));
            $netAmount = (float) ($payout?->amount ?? round($grossAmount - $fee, 2));
            $paymentType = $recap->is_expedited_requested ? 'event_pay_expedited' : 'event_pay';
            $approvalDate = $recap->approved_at ?? $payout?->approved_at;
            $status = $this->forecastRecapPayrollStatus($recap, $batchItem, $payout);

            return [
                'source_type' => 'recap',
                'source_id' => $recap->id,
                'event_id' => $recap->event_id,
                'ambassador_id' => $recap->ambassador_id,
                'ambassador_name' => $this->formatForecastUserName($recap->ambassador),
                'ambassador_email' => $recap->ambassador?->email,
                'event_name' => $recap->event?->event_name ?? $recap->event?->name ?? 'Event #' . $recap->event_id,
                'brand_name' => $recap->event?->brand?->brand_name,
                'location' => $recap->event?->location?->address,
                'event_date' => $recap->event?->start_time,
                'payment_type' => $paymentType,
                'payment_type_label' => $paymentType === 'event_pay_expedited' ? 'Event Pay (Expedited)' : 'Event Pay',
                'gross_amount' => round($grossAmount, 2),
                'fee' => round($fee, 2),
                'net_amount' => round($netAmount, 2),
                'approval_date' => $approvalDate,
                'payroll_status' => $status,
                'status_tone' => $this->forecastStatusTone($status),
                'sort_date' => $approvalDate ?? $recap->created_at,
            ];
        });

        $reimbursementRows = $reimbursements->map(function ($reimbursement) {
            $grossAmount = (float) $reimbursement->amount;
            $status = $this->forecastReimbursementPayrollStatus($reimbursement);

            return [
                'source_type' => 'reimbursement',
                'source_id' => $reimbursement->id,
                'event_id' => $reimbursement->event_id,
                'ambassador_id' => $reimbursement->user_id,
                'ambassador_name' => $this->formatForecastUserName($reimbursement->user),
                'ambassador_email' => $reimbursement->user?->email,
                'event_name' => $reimbursement->event?->event_name
                    ?? $reimbursement->event?->name
                    ?? ($reimbursement->event_id ? 'Event #' . $reimbursement->event_id : 'Reimbursement'),
                'brand_name' => $reimbursement->event?->brand?->brand_name ?? $reimbursement->brand?->brand_name,
                'location' => $reimbursement->event?->location?->address,
                'event_date' => $reimbursement->event?->start_time ?? $reimbursement->expense_date,
                'payment_type' => 'reimbursement',
                'payment_type_label' => 'Reimbursement',
                'gross_amount' => round($grossAmount, 2),
                'fee' => 0.00,
                'net_amount' => round($grossAmount, 2),
                'approval_date' => $reimbursement->reviewed_at,
                'payroll_status' => $status,
                'status_tone' => $this->forecastStatusTone($status),
                'sort_date' => $reimbursement->reviewed_at ?? $reimbursement->created_at,
            ];
        });

        return $recapRows
            ->concat($reimbursementRows)
            ->sortByDesc(fn ($row) => $this->forecastTimestampFor($row['sort_date'] ?? null))
            ->values();
    }

    private function calculateForecastRecapGrossAmount(Recap $recap): float
    {
        $wage = (float) ($recap->ambassador?->wage ?? 0);
        $baseAmount = $recap->manager_override_amount !== null
            ? (float) $recap->manager_override_amount
            : (float) ($recap->event?->getAmbassadorAmount($wage) ?? 0);

        return round(
            $baseAmount
            + (float) ($recap->assignment_override_pay ?? 0)
            + (float) ($recap->assignment_pinata_amount ?? 0),
            2
        );
    }

    private function forecastRecapPayrollStatus(Recap $recap, ?PayrollBatchItem $batchItem, ?AmbassadorPayout $payout): string
    {
        if ($batchItem) {
            return ucfirst(str_replace('_', ' ', (string) $batchItem->status));
        }

        if ($payout) {
            return ucfirst(str_replace('_', ' ', (string) $payout->status));
        }

        if (! empty($recap->denied_at) || ! empty($recap->deny_reason)) {
            return 'Rejected';
        }

        return $recap->is_approved ? 'Approved' : 'Pending';
    }

    private function forecastReimbursementPayrollStatus(Reimbursement $reimbursement): string
    {
        return $reimbursement->status === 'paid' ? 'Paid' : ucfirst((string) $reimbursement->status);
    }

    private function forecastStatusTone(string $status): string
    {
        return match (strtolower($status)) {
            'paid', 'completed' => 'success',
            'approved', 'created', 'queued', 'processing' => 'primary',
            'pending' => 'warning',
            'failed', 'job failed', 'cancelled', 'rejected' => 'danger',
            default => 'medium',
        };
    }

    private function formatForecastUserName(?User $user): string
    {
        if (! $user) {
            return '-';
        }

        $name = trim((string) ($user->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

        return $name !== '' ? $name : '-';
    }

    private function forecastTimestampFor($date): int
    {
        if (! $date) {
            return 0;
        }

        return $date instanceof \DateTimeInterface
            ? $date->getTimestamp()
            : Carbon::parse($date)->getTimestamp();
    }
    
    public function getPayments(Request $request) {
        $ambassadors = User::select('id', 'first_name', 'last_name', 'name')
            ->where('role_id', 5)
            ->orderBy('name')
            ->get();

        $query = PayrollBatchItem::query()
            ->where('payment_type', 'recap')
            ->with([
                'ambassador',
                'recap.event.brand',
                'recap.event.location',
            ]);

        if ($request->filled('startDate')) {
            $query->whereHas('recap.event', fn ($q) => $q->whereDate('start_time', '>=', $request->startDate));
        }

        if ($request->filled('endDate')) {
            $query->whereHas('recap.event', fn ($q) => $q->whereDate('start_time', '<=', $request->endDate));
        }

        if ($request->filled('ambassador_id')) {
            $query->where('ambassador_id', $request->ambassador_id);
        }

        if ($request->filled('first_name')) {
            $query->where('ambassador_id', $request->first_name);
        }

        if ($request->filled('last_name')) {
            $query->where('ambassador_id', $request->last_name);
        }

        if ($request->filled('statuses')) {
            $statuses = collect((array) $request->statuses)
                ->map(fn ($status) => $status === 'error' ? 'failed' : $status)
                ->reject(fn ($status) => $status === 'all')
                ->values()
                ->all();

            if (! empty($statuses)) {
                $query->whereIn('status', $statuses);
            }
        }

        $perPage = max(1, min((int) $request->input('per_page', $request->input('length', 20)), 50));
        $page = max(1, (int) $request->input('page', floor(((int) $request->input('start', 0)) / $perPage) + 1));
        $total = (clone $query)->count();
        $items = $query->orderByDesc('id')->forPage($page, $perPage)->get();
        $rows = $this->buildPaymentRows($items);

        return $this->successResponse('Data fetched successfully', [
            "draw"              => (int)$request->draw,
            'data'              => $rows,
            'rows'              => $rows,
            'recordsTotal'      => $total,
            'recordsFiltered'   => $total,
            'total'             => $total,
            'current_page'      => $page,
            'per_page'          => $perPage,
            'last_page'         => (int) ceil($total / $perPage),
            'ambassadors'       => $ambassadors
        ]);
    }

    private function buildPaymentRows($items)
    {
        $assignments = AssignAmbassador::query()
            ->whereIn('event_id', $items->map(fn ($item) => $item->recap?->event_id)->filter()->unique()->values())
            ->whereIn('ambassador_id', $items->pluck('ambassador_id')->unique()->values())
            ->get()
            ->keyBy(fn ($assignment) => $assignment->event_id . ':' . $assignment->ambassador_id);

        return $items->map(function ($item) use ($assignments) {
            $event = $item->recap?->event;
            $assignment = $event ? $assignments->get($event->id . ':' . $item->ambassador_id) : null;

            return [
                'id' => $item->id,
                'event_id' => $event?->id,
                'event_name' => $event?->event_name,
                'brand_name' => $event?->brand?->brand_name,
                'event_start' => $event?->start_time,
                'event_end' => $event?->end_time,
                'timezone_label' => $event?->timezone_label,
                'ambassador_id' => $item->ambassador_id,
                'ambassador_name' => $this->formatForecastUserName($item->ambassador),
                'amount' => round((float) $item->amount, 2),
                'is_late' => $assignment?->late_checkin_notification_sent ? 'Yes' : 'No',
                'created_at' => $item->created_at,
                'status' => $item->status ?? 'created',
            ];
        })->values();
    }

    public function getReimbursements(Request $request)
    {
        $query = Reimbursement::query()
            ->with(['user', 'event.brand', 'brand', 'reviewer']);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $perPage = max(1, min((int) $request->input('per_page', $request->input('length', 20)), 50));
        $page = max(1, (int) $request->input('page', floor(((int) $request->input('start', 0)) / $perPage) + 1));
        $total = (clone $query)->count();
        $items = $query->orderByDesc('created_at')->forPage($page, $perPage)->get();
        $rows = $this->buildReimbursementRows($items);

        return $this->successResponse('Data fetched successfully', [
            'draw' => (int) $request->draw,
            'data' => $rows,
            'rows' => $rows,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'total' => $total,
            'current_page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / $perPage),
        ]);
    }

    private function buildReimbursementRows($items)
    {
        return $items->map(function ($reimbursement) {
            return $this->formatAdminReimbursementForApi($reimbursement);
        })->values();
    }

    public function getReimbursementById(Request $request)
    {
        $reimbursement = Reimbursement::with(['user', 'event.brand', 'brand', 'reviewer'])
            ->find((int) $request->id);

        if (! $reimbursement) {
            return $this->errorResponse('Reimbursement not found', [], 404);
        }

        return $this->successResponse('Reimbursement loaded.', [
            'reimbursement' => $this->formatAdminReimbursementForApi($reimbursement),
            'potential_duplicates' => $this->getReimbursementPotentialDuplicates($reimbursement)
                ->map(fn ($duplicate) => $this->formatAdminReimbursementForApi($duplicate))
                ->values(),
        ]);
    }

    public function approveReimbursement(Request $request)
    {
        $reimbursement = Reimbursement::with(['user', 'event.brand', 'brand', 'reviewer'])
            ->find((int) $request->id);

        if (! $reimbursement) {
            return $this->errorResponse('Reimbursement not found', [], 404);
        }

        if ($reimbursement->status !== 'pending') {
            return $this->errorResponse('This reimbursement has already been processed.', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $potentialDuplicates = $this->getReimbursementPotentialDuplicates($reimbursement);
        if ($potentialDuplicates->isNotEmpty() && ! $request->boolean('override_duplicate_check')) {
            return $this->errorResponse('Potential duplicate reimbursement(s) found. Please review and confirm before approving.', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $reimbursement->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        return $this->successResponse(
            'Reimbursement approved. It will be included in standard Batch Payroll.',
            $this->formatAdminReimbursementForApi($reimbursement->fresh(['user', 'event.brand', 'brand', 'reviewer']))
        );
    }

    public function rejectReimbursement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'rejection_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 'validation_error', 422);
        }

        $reimbursement = Reimbursement::with(['user', 'event.brand', 'brand', 'reviewer'])
            ->find((int) $request->id);

        if (! $reimbursement) {
            return $this->errorResponse('Reimbursement not found', [], 404);
        }

        if ($reimbursement->status !== 'pending') {
            return $this->errorResponse('This reimbursement has already been processed.', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $reimbursement->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        $ba = $reimbursement->user;
        if ($ba && $ba->email) {
            $content = 'Your reimbursement request has been <strong>rejected</strong>.'
                . '<br><br>'
                . '<b>Reimbursement details:</b><br>'
                . 'Amount: $' . number_format((float) $reimbursement->amount, 2) . ' &nbsp;|&nbsp; Date: ' . $reimbursement->expense_date->format('M d, Y')
                . '<br><br>'
                . '<b>Reason for rejection:</b><br>' . nl2br(e($request->rejection_reason))
                . '<br><br>'
                . 'If you have questions, please reach out to your manager or admin.';

            $plainMessage = trim(preg_replace('/\s+/', ' ', strip_tags((string) $content)));
            $plainMessage = mb_substr($plainMessage, 0, 200);

            app(NotificationDispatchService::class)->dispatch(
                Auth::id(),
                $ba->id,
                'reimbursement',
                'Reimbursement Request Rejected',
                $plainMessage,
                $content,
                (int) $reimbursement->id
            );
        }

        return $this->successResponse(
            'Reimbursement rejected.',
            $this->formatAdminReimbursementForApi($reimbursement->fresh(['user', 'event.brand', 'brand', 'reviewer']))
        );
    }

    private function formatAdminReimbursementForApi(Reimbursement $reimbursement): array
    {
        $brandEvent = $reimbursement->event
            ? (($reimbursement->event->brand->brand_name ?? '-') . ' (Event #' . $reimbursement->event_id . ')')
            : ($reimbursement->brand->brand_name ?? $reimbursement->brand_name ?? '-');

        return [
            'id' => $reimbursement->id,
            'ambassador_id' => $reimbursement->user_id,
            'ambassador_name' => $this->formatForecastUserName($reimbursement->user),
            'expense_date' => $reimbursement->expense_date?->format('Y-m-d'),
            'expense_date_formatted' => $reimbursement->expense_date?->format('M d, Y') ?? '-',
            'brand_event' => $brandEvent,
            'amount' => round((float) $reimbursement->amount, 2),
            'amount_formatted' => '$' . number_format((float) $reimbursement->amount, 2),
            'reason' => $reimbursement->reason,
            'status' => $reimbursement->status,
            'status_label' => ucfirst((string) $reimbursement->status),
            'submitted_at' => $reimbursement->created_at?->format('M d, Y H:i'),
            'reviewed_at' => $reimbursement->reviewed_at?->format('M d, Y'),
            'reviewer_name' => $this->formatForecastUserName($reimbursement->reviewer),
            'paid_at' => $reimbursement->paid_at?->format('M d, Y'),
            'rejection_reason' => $reimbursement->rejection_reason,
            'receipt_url' => $reimbursement->receipt_path ? asset('storage/' . $reimbursement->receipt_path) : null,
            'can_review' => $reimbursement->status === 'pending',
        ];
    }

    private function getReimbursementPotentialDuplicates(Reimbursement $reimbursement)
    {
        return Reimbursement::with(['user', 'event.brand', 'brand', 'reviewer'])
            ->where('id', '!=', $reimbursement->id)
            ->where('user_id', $reimbursement->user_id)
            ->where('status', '!=', 'rejected')
            ->where('created_at', '>=', now()->subDays(90))
            ->where(function ($query) use ($reimbursement) {
                $query->where('amount', $reimbursement->amount);

                if (! empty($reimbursement->event_id)) {
                    $query->orWhere('event_id', $reimbursement->event_id);
                }
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
    }

    public function generatePayroll(Request $request, PayrollBatchService $service)
    {
        $validator = Validator::make($request->all(), [
            'generate_start_date' => 'required|date',
            'generate_end_date'   => 'required|date|after_or_equal:generate_start_date',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 'validation_error', 422);
        }

        try {
            $batch = $service->generate(
                $request->generate_start_date,
                $request->generate_end_date
            );

            return response()->json([
                'status'        => 'success',
                'batch_number'  => $batch->batch_number,
                'period_start'  => $batch->period_start->format('m/d/Y'),
                'period_end'    => $batch->period_end->format('m/d/Y'),
                'total_records' => $batch->total_records,
                'total_amount'  => number_format($batch->total_amount, 2),
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Payroll batch generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Payroll generation failed. Please try again.',
            ], 500);
        }
    }

    public function processPayroll(Request $request)
    {
        $batchIds = PayrollBatchItem::where('status', 'created')
            ->distinct()
            ->pluck('batch_id');

        if ($batchIds->isEmpty()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No payments in Created status found to process.',
            ], 422);
        }

        $userId = Auth::id();
        $count = 0;

        foreach ($batchIds as $batchId) {
            ProcessPayrollBatch::dispatch($batchId, $userId)->onQueue('default');
            $count++;
        }

        Log::info('[ProcessPayroll] Dispatched batch jobs from API', [
            'batch_ids' => $batchIds->toArray(),
            'user_id' => $userId,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Payroll processing started for ' . $count . ' batch(es).',
        ]);
    }
    
    public function getBulkPreview(Request $request){
        $rawInput = $request->notes;
        $ids = preg_split('/[\s,]+/', $rawInput, -1, PREG_SPLIT_NO_EMPTY);
        $events = Event::whereIn('id', $ids)->get();
        return $events;
    }
    
    public function getMarkets(Request $request){
        $user = JWTAuth::user();

        return $this->marketsForUser($user);
    }
    
    public function getEventDetails(Request $request){
        $user = JWTAuth::user();
        if($user){
            $this->eventService->markEndedEventsCompletedForApi();

            $event = Event::with('distributor','department','venueType','brand','location','eventType','market','markets','eventAttachments','billingParties.client.clientProfile')->where('id',$request->id)->orderBy('id','DESC')->first();
            if ($event) {
                $allowed = $this->posUserAllowedMarketIds($user);
                if ($allowed !== null && ! in_array((int) $event->market_id, $allowed, true)) {
                    return $this->errorResponse('Event not found', [], 404);
                }
            }
            $recaps = Recap::with('ambassador')->where('event_id',$request->id)->get();
            if($event){
                $event->start_time_raw = $event->start_time;
                $event->end_time_raw = $event->end_time;
                $tzFormatted = app(EventTimezoneService::class)->formatEventTimesForApi($event);
                $event->start_time = $tzFormatted['start_time'] ?? $event->start_time;
                $event->end_time = $tzFormatted['end_time'] ?? $event->end_time;
                $event->time_display = $tzFormatted['time_display'] ?? null;
                $event->timezone_label = $tzFormatted['timezone_label'] ?? $event->timezone_label;
                $event->datetime = $event->FormattedTime;
                $event->event_location_label = app(EventCheckInService::class)->eventLocationLabel($event);

                // If no billing parties exist, load client directly from client_id
                if ($event->billingParties->isEmpty() && $event->client_id) {
                    $billClient = User::with('clientProfile')
                        ->where('role_id', 3)
                        ->find($event->client_id);
                    if ($billClient) {
                        $event->billing_parties_data = [[
                            'client_id'  => $billClient->id,
                            'is_primary' => true,
                            'labor_rate' => $event->labor_amount ?? null,
                            'client'     => [
                                'id'                      => $billClient->id,
                                'name'                    => $billClient->name,
                                'email'                   => $billClient->email,
                                'phone'                   => $billClient->phone,
                                'company_name'            => $billClient->company_name,
                                'address'                 => $billClient->address,
                                'city'                    => $billClient->clientProfile?->city ?? $billClient->city,
                                'state'                   => $billClient->clientProfile?->state ?? $billClient->state,
                                'zip_code'                => $billClient->clientProfile?->zip_code ?? $billClient->zip_code,
                                'default_labor_amount'    => $billClient->default_labor_amount,
                                'default_buyback_amount'  => $billClient->default_buyback_amount,
                                'default_cups_amount'     => $billClient->default_cups_amount,
                                'primary_billing_contact' => $billClient->primary_billing_contact,
                            ],
                        ]];
                    }
                } else {
                    $event->billing_parties_data = $event->billingParties->map(fn($bp) => [
                        'client_id'  => $bp->client_id,
                        'is_primary' => (bool) $bp->is_primary,
                        'labor_rate' => $bp->labor_rate,
                        'client'     => $bp->client ? [
                            'id'                      => $bp->client->id,
                            'name'                    => $bp->client->name,
                            'email'                   => $bp->client->email,
                            'phone'                   => $bp->client->phone,
                            'company_name'            => $bp->client->company_name,
                            'address'                 => $bp->client->address,
                            'city'                    => $bp->client->clientProfile?->city ?? $bp->client->city,
                            'state'                   => $bp->client->clientProfile?->state ?? $bp->client->state,
                            'zip_code'                => $bp->client->clientProfile?->zip_code ?? $bp->client->zip_code,
                            'default_labor_amount'    => $bp->client->default_labor_amount,
                            'default_buyback_amount'  => $bp->client->default_buyback_amount,
                            'default_cups_amount'     => $bp->client->default_cups_amount,
                            'primary_billing_contact' => $bp->client->primary_billing_contact,
                        ] : null,
                    ])->values()->toArray();
                }

                $getAmbassador = AssignAmbassador::where(['event_id' => $event->id, 'status' => '1'])->pluck('ambassador_id')->toArray();
                if($getAmbassador) {
                    $ambData = User::whereIn('id',$getAmbassador)->pluck('name')->toArray();
                    $name = implode(",",$ambData);
                    $event->assigned_person=$name;
                    $event->ambassadors=User::select('id','name','phone')->whereIn('id',$getAmbassador)->get();
                }
            }
            return $this->successResponse('Event Data.', ['event'=>$event,'recaps'=>$recaps]);    
        }else{
            return $this->errorResponse('User Not Found', [], 403);
        }
    }
    
    public function deleteEvent(Request $request) {
        $user = JWTAuth::user();
        if (!$user || !in_array((int) $user->role_id, [1, 6], true)) {
            return $this->errorResponse('Unauthorized', 'forbidden', Response::HTTP_FORBIDDEN, new \stdClass());
        }
        $id = $request->input('id');
        if (!$id) {
            return $this->errorResponse('Event ID is required.', 'validation_error', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
        }
        $event = Event::find($id);
        if (!$event) {
            return $this->errorResponse('Event not found.', 'not_found', Response::HTTP_NOT_FOUND, new \stdClass());
        }
        $event->delete();
        return $this->successResponse('Event deleted successfully.', []);
    }

    public function deleteAmbassador(Request $request){
        $id = $request->id;
        $user = User::find($id);
        if($user) {
            $user->delete();
            return $this->successResponse('Ambassador Deleted Successfully!', []);   
        }
        return $this->errorResponse('User Not Found', [], 403);
    }
    
    public function getMarketState(Request $request){
        $user = JWTAuth::user();
        $state = State::get();
        $market = $this->marketsForUser($user);
        return $this->successResponse('Data Fetched Successfully!', [
            'state'    =>  $state,
            'market'   =>  $market
        ]);   
    }
    public function addUpdateAmbassdor(Request $request){
        $user = JWTAuth::user();

        $id = $request->id;
        $addressRequired = $id ? 'nullable' : 'required';
        $validator = Validator::make($request->all(), [
            'id'          => ['nullable', 'integer', 'exists:users,id'],
            'first_name'  => ['required', 'string'],
            'last_name'   => ['required', 'string'],
            'email'       => ['required', 'email', Rule::unique('users', 'email')->ignore($id)],
            'password'    => [$id ? 'nullable' : 'required', 'string'],
            'dob'         => ['required', 'date', 'before_or_equal:' . Carbon::now()->subYears(18)->format('Y-m-d')],
            'hired'       => ['required', 'date'],
            'region'      => ['required', 'string'],
            'phone'       => ['required', 'string'],
            'legal_address'   => [$addressRequired, 'string'],
            'legal_city'      => [$addressRequired, 'string'],
            'legal_state'     => [$addressRequired, 'string'],
            'legal_zip_code'  => [$addressRequired, 'digits_between:5,6'],
            'wage'        => ['required'],
            'market_id'   => ['required'],
            'shirt_size'  => ['required'],
            'status'      => ['required', Rule::in(['0', '1', '2', 0, 1, 2])],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 'VALIDATION_ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, $validator->errors());
        }

        try {
            $userData = DB::transaction(function () use ($request, $id) {
                if ($id) {
                    $userData = User::lockForUpdate()->findOrFail($id);
                } else {
                    $userData = new User();
                    $userData->email = trim((string) $request->email);
                    $userData->password = Hash::make($request->password);
                    $userData->role_id = 5;
                }

                // ── users table: identity + auth only ───────────────────────────────
                $userData->first_name = $request->first_name;
                $userData->last_name  = $request->last_name;
                $userData->name       = $request->first_name . ' ' . $request->last_name;
                $userData->phone      = $request->phone;
                $userData->status     = $request->status;
                $userData->save();

                // ── ambassadors table: all profile-specific data ─────────────────
                $sameAsLegal  = (bool) $request->same_as_legal;
                $mailingAddr  = $sameAsLegal ? $request->legal_address  : $request->mailing_address;
                $mailingCity  = $sameAsLegal ? $request->legal_city     : $request->mailing_city;
                $mailingState = $sameAsLegal ? $request->legal_state     : $request->mailing_state;
                $mailingZip   = $sameAsLegal ? $request->legal_zip_code : $request->mailing_zip_code;

                $ambassadorProfile = $userData->ambassadorProfile()->updateOrCreate(
                    ['user_id' => $userData->id],
                    [
                        'dob'                    => $request->dob,
                        'nickname'               => $request->nickname,
                        'venmo'                  => $request->venmo,
                        'hired'                  => $request->hired,
                        'wage'                   => $request->wage,
                        // market_id column removed — markets now stored in ambassador_market pivot
                        'shirt_size'             => $request->shirt_size,
                        'region'                 => $request->region,
                        'latitude'               => $request->filled('latitude') ? $request->latitude : null,
                        'longitude'              => $request->filled('longitude') ? $request->longitude : null,

                        // Legal address — state as short code
                        'legal_address'          => $request->legal_address,
                        'legal_city'             => $request->legal_city,
                        'legal_state'            => $request->legal_state ? strtoupper(trim((string) $request->legal_state)) : null,
                        'legal_zip_code'         => $request->legal_zip_code,

                        // Mailing address
                        'same_as_legal'          => $sameAsLegal,
                        'mailing_address'        => $mailingAddr,
                        'mailing_city'           => $mailingCity,
                        'mailing_state'          => $mailingState ? strtoupper(trim((string) $mailingState)) : null,
                        'mailing_zip_code'       => $mailingZip,

                        // Certifications
                        'titos_trained'          => $request->boolean('titos_trained'),
                        'attended_trainings'     => $request->boolean('attended_trainings'),
                        'thc_quiz_passed'        => $request->boolean('thc_quiz_passed'),
                        'tips_certified'         => $request->boolean('tips_certified'),
                        'male_ba_lgbtq_ok'       => $request->boolean('male_ba_lgbtq_ok'),
                        'has_permit'             => $request->boolean('has_permit'),

                        // About fields
                        'favorite_coffee_shop'   => $request->favorite_coffee_shop,
                        'favorite_restaurant'    => $request->favorite_restaurant,
                        'favorite_place_to_shop' => $request->favorite_place_to_shop,
                        'favorite_charity'       => $request->favorite_charity,
                    ]
                );

                // ── Sync markets via pivot table (ambassador_market) ─────────────────
                // Accepts single ID (string/int) or array — normalise to int array.
                if ($request->filled('market_id')) {
                    $marketIds = is_array($request->market_id)
                        ? array_map('intval', $request->market_id)
                        : array_filter(array_map('intval', explode(',', (string) $request->market_id)));

                    // sync() removes stale pivot rows and prevents duplicates
                    $ambassadorProfile->markets()->sync($marketIds);
                }

                return $userData;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return $this->errorResponse('An Ambassador with this email already exists.', 'DUPLICATE_AMBASSADOR', Response::HTTP_CONFLICT, []);
            }
            throw $e;
        }

        // Reload with ambassador profile + markets so response includes updated fields
        $userData->load('ambassadorProfile.markets');
        if (isset($request->id)) {
            return $this->successResponse('Updated Ambassador Successfully', $userData);
        } else {
            return $this->successResponse('Created Ambassador Successfully', $userData);
        }
    }
    
    public function getRecapById(Request $request){
        $user = JWTAuth::user();
        if(isset($request->id)){
            $recap = Recap::with(
                'event.location',
                'event.brand',
                'event.eventType',
                'event.distributor',
                'brand.user',
                'client',
                'state',
                'ambassador'
            )->find($request->id);
            if (! $recap) {
                return $this->errorResponse('Recap not found', [], 404);
            }

            $event = $recap->event;
            $recapType = \App\Services\RecapFormService::resolveForRecap($recap);
            $assignedAmbassadorIds = array_filter(explode(',', (string) ($event->ambassador_id ?? '')));
            $assignedAmbassadors = User::select('id', 'name', 'email')
                ->whereIn('id', $assignedAmbassadorIds)
                ->get();
            $ambassadorNames = $assignedAmbassadors->pluck('name')->filter()->join(', ');
            $brand = $recap->brand ?? $event?->brand;

            $eventDetails = [
                ['label' => 'Store', 'value' => $recap->state->name ?? '--'],
                ['label' => 'Brand', 'value' => $event->brand->brand_name ?? 'N/A'],
                ['label' => 'Event ID', 'value' => (string) $recap->event_id],
                ['label' => 'Event Type', 'value' => $event->eventType->name ?? 'Not set'],
                ['label' => 'Recap Form', 'value' => \App\Services\RecapFormService::displayLabel($recapType)],
                ['label' => 'Date', 'value' => $event->FormattedDate ?? 'Not available'],
                [
                    'label' => 'Time',
                    'value' => ($event?->start_time ? Carbon::parse($event->start_time)->format('h:i A') : 'Not available')
                        .' - '.
                        ($event?->end_time ? Carbon::parse($event->end_time)->format('h:i A') : 'Not available'),
                ],
                ['label' => 'Ambassadors', 'value' => $ambassadorNames ?: ($recap->ambassador->name ?? 'Not available')],
            ];

            $recapDetails = [];
            if ($recapType !== \App\Services\RecapFormService::TYPE_OFF_PREMISE) {
                $isSpecialEventRecap = $recapType === \App\Services\RecapFormService::TYPE_SPECIAL_EVENT;
                $recapDetails = array_merge($recapDetails, [
                    ['label' => "Describe the location's traffic (Registers, Aisles, Other parts of location)", 'value' => $recap->traffic_location ?? '-'],
                    ['label' => 'Was the timing of this event successful?', 'value' => $recap->close_time ? Carbon::parse($recap->close_time)->format('Y-m-d H:i') : 'Not available'],
                    ['label' => 'With whom did you check in? Provide name, phone and/or email', 'value' => $recap->check_in_with_person ?? 'Not available'],
                ]);

                if (! $isSpecialEventRecap) {
                    $recapDetails = array_merge($recapDetails, [
                        ['label' => 'Is there a promotion running for this product?', 'value' => $recap->promotion_product_status ? 'Yes' : 'No'],
                        ['label' => 'Everyday Price Tip (line break) What is the Feature/Special Pricing?', 'value' => $recap->tip ?? 'Not available'],
                        ['label' => 'If competitor brands were having demos on-site, list their names, setup, location and price point.', 'value' => $recap->brand_dmeo_name ?? 'Not available'],
                    ]);
                }

                $recapDetails = array_merge($recapDetails, [
                    ['label' => 'Back up brand used', 'value' => $recap->backup_brand_used ? 'Yes' : 'No'],
                ]);
                if ($recap->backup_brand_used && $recap->backup_brand_name) {
                    $recapDetails[] = ['label' => 'Backup brand name', 'value' => $recap->backup_brand_name];
                }
                $recapDetails = array_merge($recapDetails, [
                    ['label' => 'What was the predominant gender of consumers you interacted with?', 'value' => $recap->interaction_gender ? ucfirst($recap->interaction_gender) : 'Not available'],
                    ['label' => 'What was the predominant age of consumers you interacted with?', 'value' => $recap->interaction_age ?? 'Not available'],
                    ['label' => 'Approximately how many consumers did you interact with?', 'value' => $recap->total_interaction ?? 'Not available'],
                ]);

                if (! $isSpecialEventRecap) {
                    $recapDetails = array_merge($recapDetails, [
                        ['label' => 'How many samples did you hand out?', 'value' => $recap->samples ?? 'Not available'],
                        ['label' => 'What type of product were you selling?', 'value' => $brand->brand_name ?? 'Not available'],
                        ['label' => 'How many units of each product were sold during your task?', 'value' => $recap->sold_unit ?? 'Not available'],
                    ]);
                }

                $recapDetails[] = [
                    'label' => 'Buyback amount ($)',
                    'value' => $recap->buyback_amount !== null ? '$'.number_format((float) $recap->buyback_amount, 2) : 'Not available',
                ];
            }

            $recapDetails = array_merge($recapDetails, $this->recapTypeDisplayDetails($recap, $recapType), [
                ['label' => 'State', 'value' => $recap->state->name ?? 'Not available'],
            ]);

            if ($recapType !== \App\Services\RecapFormService::TYPE_SPECIAL_EVENT) {
                $recapDetails[] = ['label' => 'How many people knew about the brand?', 'value' => $recap->brand_count ?? '-'];
            }

            return $this->successResponse('Data Fetched Successfully!',[
                'recap'=>$recap,
                'event' => $event,
                'recap_type' => $recapType,
                'recap_form_label' => \App\Services\RecapFormService::displayLabel($recapType),
                'event_details' => $eventDetails,
                'recap_details' => $recapDetails,
           ]);   
        }
        
    }

    protected function recapTypeDisplayDetails(Recap $recap, string $recapType): array
    {
        if ($recapType === \App\Services\RecapFormService::TYPE_OFF_PREMISE) {
            $productsSold = $recap->products_sold;
            if (is_string($productsSold)) {
                $productsSold = json_decode($productsSold, true);
            }

            return [
                ['label' => 'Describe Traffic Flow', 'value' => $recap->traffic_flow ?? 'Not available'],
                ['label' => 'Demographics - Female', 'value' => $recap->demographics_female !== null ? $recap->demographics_female.'%' : 'Not available'],
                ['label' => 'Demographics - Male', 'value' => $recap->demographics_male !== null ? $recap->demographics_male.'%' : 'Not available'],
                ['label' => 'Demographics - Age Range', 'value' => $recap->demographics_age_range ?? 'Not available'],
                ['label' => 'Sampling Number', 'value' => $recap->sampling_number ?? 'Not available'],
                ['label' => 'Products Sold', 'value' => ! empty($productsSold) && is_array($productsSold) ? collect($productsSold)->map(fn ($product) => trim(($product['type'] ?? '-').' / '.($product['name'] ?? '-').' / Sold: '.($product['sold'] ?? '-')))->join("\n") : 'Not available'],
                ['label' => 'Receipt Total', 'value' => $recap->receipt_total !== null ? '$'.number_format((float) $recap->receipt_total, 2) : 'Not available'],
                ['label' => 'Parking Fee', 'value' => $recap->op_parking_fee !== null ? '$'.number_format((float) $recap->op_parking_fee, 2) : 'Not available'],
                ['label' => 'Store Aware of Demo?', 'value' => $recap->store_aware_of_demo ? 'Yes' : 'No'],
                ['label' => 'Store Prepared for Demo?', 'value' => $recap->store_prepared_for_demo ? 'Yes' : 'No'],
                ['label' => 'Product on Sale?', 'value' => $recap->product_on_sale ? 'Yes' : 'No'],
                ['label' => 'Coupons / Rebates Available?', 'value' => $recap->coupons_rebates_available ? 'Yes' : 'No'],
                ['label' => 'Enough Product in Stock?', 'value' => $recap->enough_product_in_stock ? 'Yes' : 'No'],
                ['label' => 'Case Display?', 'value' => $recap->case_display ? 'Yes'.($recap->case_display_count ? ' - '.$recap->case_display_count : '') : 'No'],
                ['label' => 'Objections to the Sale', 'value' => $recap->objections_to_sale ?? 'Not available'],
                ['label' => 'Consumer Comments / Suggestions', 'value' => $recap->consumer_comments_suggestions ?? 'Not available'],
                ['label' => 'Account Feedback', 'value' => $recap->account_feedback ?? 'Not available'],
            ];
        }

        if ($recapType === \App\Services\RecapFormService::TYPE_SPECIAL_EVENT) {
            return [
                ['label' => 'Consumer Interactions', 'value' => $recap->consumer_interactions ?? 'Not available'],
                ['label' => 'Consumer Sampled', 'value' => $recap->consumer_sampled ?? 'Not available'],
                ['label' => 'Event Summary', 'value' => $recap->event_summary ?? 'Not available'],
                ['label' => 'Please list all relevant consumer feedback.', 'value' => $recap->consumer_feedback ?? 'Not available'],
                ['label' => 'Ambassador Feedback', 'value' => $recap->ambassador_feedback ?? 'Not available'],
            ];
        }

        if ($recapType === \App\Services\RecapFormService::TYPE_ON_PREMISE) {
            return [
                ['label' => 'Drinks Purchased After Sampling (Consumer)', 'value' => $recap->drinks_purchased_after_sampling ?? 'Not available'],
                ['label' => 'Consumer Comments', 'value' => $recap->consumer_feedback ?? 'Not available'],
                ['label' => 'Manager Comments', 'value' => $recap->manager_comments ?? 'Not available'],
                ['label' => 'Consumer Comments / Suggestions', 'value' => $recap->consumer_comments_suggestions ?? 'Not available'],
            ];
        }

        return [
            ['label' => 'Please list all relevant consumer feedback.', 'value' => $recap->consumer_feedback ?? 'Not available'],
            ['label' => 'Ambassador Feedback', 'value' => $recap->ambassador_feedback ?? 'Not available'],
        ];
    }
    
    public function addUpdateRecap(Request $request){
        $user = JWTAuth::user();
        
        if(isset($request->id)){
            $recap = Recap::find($request->id);
        }else{
            $recap = new Recap();
        }
        
        if ($request->product_attachments1) {
            $image = $request->file('product_attachments1');
            if ($image->isValid()) {
                $imagename = time() . '.' . $image->getClientOriginalExtension();
                $image->move('product_attachments1', $imagename);
                $recap->product_attachments1 = asset('product_attachments1/' . $imagename);
            } else {
                return $this->errorResponse("Invalid File", 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
            }
        }
        if ($request->product_attachments2) {
            $image = $request->file('product_attachments2');
            if ($image->isValid()) {
                $imagename = time() . '.' . $image->getClientOriginalExtension();
                $image->move('product_attachments2', $imagename);
                $recap->product_attachments2 = asset('product_attachments2/' . $imagename);
            } else {
                return $this->errorResponse("Invalid File", 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
            }
        }
        if ($request->brand_photo) {
            $image = $request->file('brand_photo');
            if ($image->isValid()) {
                $imagename = time() . '.' . $image->getClientOriginalExtension();
                $image->move('brand_photo', $imagename);
                $recap->brand_photo = asset('brand_photo/' . $imagename);
            } else {
                return $this->errorResponse("Invalid File", 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
            }
        }
        if ($request->consumer_photo) {
            $image = $request->file('consumer_photo');
            if ($image->isValid()) {
                $imagename = time() . '.' . $image->getClientOriginalExtension();
                $image->move('consumer_photo', $imagename);
                $recap->consumer_photo = asset('consumer_photo/' . $imagename);
            } else {
                return $this->errorResponse("Invalid File", 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
            }
        }
        if ($request->receipt) {
            $image = $request->file('receipt');
            if ($image->isValid()) {
                $imagename = time() . '.' . $image->getClientOriginalExtension();
                $image->move('receipt', $imagename);
                $recap->receipt = asset('receipt/' . $imagename);
            } else {
                return $this->errorResponse("Invalid File", 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
            }
        }
        
        $recap->traffic_location = $request->traffic_location;
        $recap->close_time = $request->close_time;
        $recap->check_in_with_person = $request->check_in_with_person;
        $recap->promotion_product_status = $request->promotion_product_status;
        $recap->tip = $request->tip;
        $recap->brand_dmeo_name = $request->brand_dmeo_name;
        $recap->interaction_gender = $request->interaction_gender;
        $recap->interaction_age = $request->interaction_age;
        $recap->total_interaction = $request->total_interaction;
        $recap->brand_selling_id = $request->brand_selling_id;
        $recap->sold_unit = $request->sold_unit;
        $recap->ambassador_feedback = $request->ambassador_feedback;
        $recap->state_id = $request->state_id;
        $recap->brand_count = $request->brand_count;
        $recap->consumer_feedback = $request->consumer_feedback;
       
        $recap->save();
        if(isset($request->id)){
            return $this->successResponse('Updated Recap Successfully', $recap);
        }else{
            return $this->successResponse('Created Recap Successfully', $recap);
        }
        
    }
    
    public function updateClientProfile(Request $request){
        if(isset($request->user_id)){
            $user = User::find($request->user_id);
            if (! $user) {
                return $this->errorResponse('Client not found', 'ERROR', Response::HTTP_NOT_FOUND, new \stdClass());
            }
        }else{
            $user = new User();
        }
        $Validator = Validator::make($request->all(), [
            'first_name'=> ['bail', 'required', 'string', 'max:255'],
            'last_name'=> ['bail', 'required', 'string', 'max:255'],
            'phone'=> ['bail', 'required', 'integer','digits_between:10,12','gt:0'],
            'email'     => ['bail', 'required', 'string', 'email', 'max:255'],
            'default_labor_amount' => ['nullable', 'numeric', 'min:0'],
            'default_cups_amount' => ['nullable', 'numeric', 'min:0'],
            'default_buyback_amount' => ['nullable', 'numeric', 'min:0'],
            'default_receipt_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'budget_max_amount' => ['nullable', 'numeric', 'min:0'],
            'budget_max_events' => ['nullable', 'integer', 'min:0'],
            'budget_warn_percent' => ['nullable', 'integer', 'min:1', 'max:100'],
            'budget_alert_email' => ['nullable', 'email'],
        ]);
        if ($Validator->fails()) {
            $errors = $Validator->errors();
            foreach ($errors->all() as $key => $value) {
                return $this->errorResponse(ucfirst($value), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
            }
        }

        try {
            if ($request->profile_image) {
                $image = $request->file('profile_image');
                // Ensure the file is valid
                if ($image->isValid()) {
                    $imagename = time() . '.' . $image->getClientOriginalExtension();
                    $image->move('profile_images', $imagename);
                    $user->profile_image = '/profile_images/' . $imagename;
                } else {
                    return $this->errorResponse("Invalid File", 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
                }
            }

            $user->first_name =     $request->first_name;
            $user->last_name  =     $request->last_name;
            $user->phone    =     $request->phone ? $request->phone : $user->phone;
            $user->city    =     $request->city ? $request->city : $user->city;
            $user->address    =     $request->address ? $request->address : $user->address;
            $user->state    =     $request->state ? $request->state : $user->state;
            $user->state_id  =     $request->state_id ? $request->state_id : $user->state_id;
            $user->venue_name  =     $request->venue_name ? $request->venue_name : $user->venue_name;
            $user->venue_type  =     $request->venue_type ? $request->venue_type : $user->venue_type;
            $user->name       =     $request->first_name . ' ' . $request->last_name;
            $brandIds = $this->normalizeIdList($request->brand_id);
            $distributorIds = $this->normalizeIdList($request->distributor_id);

            $user->brand_id       =      !empty($brandIds) ? implode(',', $brandIds) : $user->brand_id;
            $user->distributor_id       =     !empty($distributorIds) ? implode(',', $distributorIds) : $user->distributor_id;
            $user->role_id       =     $request->role_id ? $request->role_id : $user->role_id;
            $user->password       =     $request->filled('password') ?  Hash::make($request->password) : $user->password;
            $user->email       =     $request->email ? $request->email : $user->email;
            $user->status       =    isset($request->status) ? $request->status : $user->status;
            $user->save();

            $profile = $user->clientProfile ?: new Client(['user_id' => $user->id]);
            $profile->contract_recieved = $request->boolean('contract_recieved') ? 1 : 0;
            $profile->default_labor_amount = $request->input('default_labor_amount');
            $profile->default_buyback_amount = $request->input('default_buyback_amount');
            $profile->default_cups_amount = $request->input('default_cups_amount');
            $profile->default_receipt_percent = $request->input('default_receipt_percent');
            $profile->address = $request->input('address');
            $profile->city = $request->input('city');
            $profile->state = $request->input('state');
            $profile->zip_code = $request->input('zip_code');
            $profile->country = $request->input('country', 'USA');
            $profile->company_name = $request->input('company_name');
            $profile->primary_billing_contact = $request->input('primary_billing_contact');
            $profile->notes = $request->input('notes');
            $profile->budget_program_name = $request->input('budget_program_name');
            $profile->budget_max_amount = $request->input('budget_max_amount');
            $profile->budget_max_events = $request->input('budget_max_events');
            $profile->budget_warn_percent = $request->input('budget_warn_percent', 80);
            $profile->budget_alert_email = $request->input('budget_alert_email');
            $profile->budget_enforce = $request->boolean('budget_enforce');
            $profile->budget_require_approval = $request->boolean('budget_require_approval');
            $profile->cc_emails = collect($request->input('cc_emails', []))
                ->map(fn ($email) => trim((string) $email))
                ->filter()
                ->values()
                ->all();
            $profile->save();

            if (! empty($brandIds)) {
                $profile->brands()->sync($brandIds);
            }
            if (! empty($distributorIds)) {
                $profile->distributors()->sync($distributorIds);
            }
           
            if(isset($request->user_id)){
                return $this->successResponse('Client Profile updated successfully.', $user->load('clientProfile'));
            }else{
                return $this->successResponse('Client Profile created successfully.', $user->load('clientProfile'));
            }
        } catch (Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
        }
    }

    protected function normalizeIdList($value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
    
    public function sendMessage(Request $request){
        $sender = JWTAuth::user();
        $recipients = [];
        foreach ($request->contents as $key=>$marketId) {
            $users = User::select('id', 'name', 'email')
                ->whereRaw('FIND_IN_SET(?, market_id)', [$marketId['market']])
                ->get();
    
            foreach ($users as $recipient) {
                $recipients[] = [
                    'id' => (int) $recipient->id,
                    'market_id' => (int) $marketId['market'],
                    'name' => $recipient->name,
                ];
                $plainMessage = trim(preg_replace('/\s+/', ' ', strip_tags((string) $marketId['content'])));
                $plainMessage = mb_substr($plainMessage, 0, 200);

                app(NotificationDispatchService::class)->dispatch(
                    $sender?->id,
                    $recipient->id,
                    'message',
                    'New Message',
                    $plainMessage,
                    (string) $marketId['content']
                );
            }
        }
    
        return $this->successResponse('Message Sent Successfully!', [
            'recipients' => $recipients,
        ]);
    }
    
    public function uploadArasDocument(Request $request){
        $user = JWTAuth::user();
        if($user){
            $request->validate([
                'document' => 'required|file|mimes:jpg,jpeg,png,pdf',
            ]);

            $file_data = '';
            if ($request->hasFile('document')) {
                $app_url    =   config('app.url');
                $path       =   public_path('documents/');
                $file       =   $request->file('document');
                $name       =   Carbon::now()->timestamp.'.'.$file->getClientOriginalExtension();
                $file_size  =   $file->getSize();
                $file->move($path, $name);  
                $file_data = ($app_url.'/public/documents/'.$name);
                
                $temp=new ArasDocuments();
                $temp->user_id=$user->id;
                $temp->file_name=$file->getClientOriginalName();
                $temp->url=$file_data;
                $temp->file_size=$file_size;
                $temp->save();
                
                return $this->successResponse('Document Uploaded Successfully!', []);
            }else {
                return $this->errorResponse('Invalid File', [], 403);
            }
        } else {
            return $this->errorResponse('User Not Found', [], 403);   
        }
    }
    
    public function getArasDocuments(Request $request){
        $user = JWTAuth::user();
        if($user){
            $temp=ArasDocuments::where('user_id',$user->id)->orderByDesc('id')->get();
            return $this->successResponse('Data fetched successfully', [
                'data'  => $temp,
                "draw"              => (int)$request->draw,
                'recordsTotal'      => $temp->count(),
                'recordsFiltered'   => $temp->count(),
            ]);
        } else {
            return $this->errorResponse('User Not Found', [], 403);   
        }
    }

    public function deleteArasDocument(Request $request){
        $user = JWTAuth::user();
        if($user){
            $request->validate([
                'id' => 'required|integer',
            ]);

            $document = ArasDocuments::where('id', $request->id)
                ->where('user_id', $user->id)
                ->first();

            if (! $document) {
                return $this->errorResponse('Document Not Found', [], 404);
            }

            $filename = basename(parse_url($document->url, PHP_URL_PATH) ?: $document->url);
            $filePath = public_path('documents/' . $filename);
            if ($filename && file_exists($filePath)) {
                unlink($filePath);
            }

            $document->delete();

            return $this->successResponse('Document deleted successfully.', []);
        } else {
            return $this->errorResponse('User Not Found', [], 403);
        }
    }

    /** GET admin/search_venues?q=text&limit=20 */
    public function searchVenues(Request $request) {
        $q = trim($request->input('q', ''));
        $limit = min((int)$request->input('limit', 20), 50);
        $query = \App\Models\EventLocation::query()
            ->select('id','name','address','city','state','zip_code','latitude','longitude','timezone','timezone_label');
        if (strlen($q) >= 2) {
            $query->where(function($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%");
            });
        }
        $results = $query->orderBy('name')->limit($limit)->get();
        return $this->successResponse('Venues fetched', $results);
    }

    /** GET admin/search_brands?q=text&limit=20 */
    public function searchBrands(Request $request) {
        $q = trim($request->input('q', ''));
        $limit = min((int)$request->input('limit', 20), 50);
        $query = \App\Models\Brand::select('id','brand_name');
        if (strlen($q) >= 2) {
            $query->where('brand_name', 'like', "%{$q}%");
        }
        $results = $query->orderBy('brand_name')->limit($limit)->get();
        return $this->successResponse('Brands fetched', $results);
    }

    /** GET admin/search_distributors?q=text&limit=20 */
    public function searchDistributors(Request $request) {
        $q = trim($request->input('q', ''));
        $limit = min((int)$request->input('limit', 20), 50);
        $query = \App\Models\EventDistributor::select('id','name');
        if (strlen($q) >= 2) {
            $query->where('name', 'like', "%{$q}%");
        }
        $results = $query->orderBy('name')->limit($limit)->get();
        return $this->successResponse('Distributors fetched', $results);
    }

    /** GET admin/search_markets?q=text&limit=20 */
    public function searchMarkets(Request $request) {
        $q = trim($request->input('q', ''));
        $limit = min((int)$request->input('limit', 20), 50);
        $query = \App\Models\Market::select('id','name');
        if (strlen($q) >= 2) {
            $query->where('name', 'like', "%{$q}%");
        }
        $results = $query->orderBy('name')->limit($limit)->get();
        return $this->successResponse('Markets fetched', $results);
    }

    /** GET admin/get_form_data — small dropdowns for event form (no large datasets) */
    /** GET admin/client_billing_defaults?client_id=X */
    public function getClientBillingDefaults(Request $request) {
        $clientId = $request->input('client_id');
        if (!$clientId) {
            return $this->errorResponse('client_id required', [], 422);
        }
        $client = \App\Models\User::where('role_id', 3)->where('id', $clientId)
            ->select('id','name','default_labor_amount','default_buyback_amount','default_cups_amount')
            ->first();
        if (!$client) {
            return $this->errorResponse('Client not found', [], 404);
        }
        return $this->successResponse('Billing defaults fetched', [
            'client_id'      => $client->id,
            'name'           => $client->name,
            'labor_amount'   => $client->default_labor_amount,
            'buyback_amount' => $client->default_buyback_amount,
            'cups_amount'    => $client->default_cups_amount,
        ]);
    }

    /** GET admin/event_billing_parties?event_id=X */
    public function getEventBillingParties(Request $request) {
        $eventId = $request->input('event_id');
        if (!$eventId) {
            return $this->errorResponse('event_id required', [], 422);
        }
        $parties = \App\Models\EventBillingParty::where('event_id', $eventId)
            ->with(['client:id,name,default_labor_amount,default_buyback_amount,default_cups_amount'])
            ->orderBy('is_primary', 'desc')
            ->orderBy('sort_order')
            ->get()
            ->map(fn($p) => [
                'client_id'  => $p->client_id,
                'name'       => $p->client->name ?? '',
                'is_primary' => (bool) $p->is_primary,
                'labor_rate' => $p->labor_rate,
            ]);
        return $this->successResponse('Billing parties fetched', $parties);
    }

    public function getEventFormData(Request $request) {
        $user = JWTAuth::user();
        $nextEventName = $this->generateEventNameFromNextId();

        return $this->successResponse('Form data fetched', [
            'auto_event_name' => $nextEventName,
            'event_name'      => $nextEventName,
            'departments'   => \App\Models\EventDepartment::select('id','name')->orderBy('name')->get(),
            'event_types'   => \App\Models\EventType::select('id','name')->orderBy('name')->get(),
            'managers'      => \App\Models\User::where('role_id', 6)->where('status','1')->select('id','name','first_name','last_name')->orderBy('name')->get(),
            'clients'       => \App\Models\User::where('role_id', 3)->select('id','name')->orderBy('name')->get(),
            'payment_types' => [
                ['value' => 'No purchase',     'label' => 'No purchase'],
                ['value' => 'Product on site', 'label' => 'Product on site'],
                ['value' => 'Comdata',         'label' => 'Comdata'],
                ['value' => 'Check',           'label' => 'Check'],
                ['value' => 'Reimbursement',   'label' => 'Reimbursement'],
            ],
            'regions' => [
                ['value' => 'Southeast', 'label' => 'Southeast'],
                ['value' => 'Midwest',   'label' => 'Midwest'],
            ],
        ]);
    }
}
    
