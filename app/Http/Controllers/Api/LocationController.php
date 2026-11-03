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
use App\Services\EventTimezoneService;
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
use App\Models\AssignAmbassador;
use DB;

class LocationController extends Controller
{
    protected $eventService;
    use FormatResponseTrait;
    
    public function __construct(EventService $eventService){
        $this->eventService = $eventService;
    }
    
    public function getStateVenueData(Request $request){
        $state = State::get();
        $venue= Venue::get();
        return $this->successResponse('Data fetched Successfully', [
            'state'        =>  $state,
            'venue'  => $venue,
        ]);
    }
    
    public function getEvents(Request $request) {
        $user = JWTAuth::user();
        if($user){
            $this->eventService->markEndedEventsCompletedForApi();

            $lastDayTimestamp = strtotime(date('Y-m-t', strtotime($request->start_date)));
            
            $events = Event::select(DB::raw('DATE(start_time) as date'),DB::raw('COUNT(*) as count'))->where('event_location_id', $user->id)
            ->whereBetween('start_time', [
                $request->start_date.' 00:00:00',
                date('Y-m-d', $lastDayTimestamp).' 23:59:59'
            ])
            ->where('event_status', '!=', '2') // Exclude cancelled — badge shows Pending + Completed only
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
    
    public function getEventsCalendarData(Request $request){
        $user = JWTAuth::user();
        if($user){
            $this->eventService->markEndedEventsCompletedForApi();
        }

        $locations = User::where('role_id',4)->get();
        $venueTypes = Venue::get();
        $departments = EventDepartment::get();
        $distributors = EventDistributor::get();
        $brands = Brand::get();
        $managers = User::where('role_id',6)->get();
        $ambassadors = User::where('role_id',5)->get();
        $markets = Market::get();
        $events = Event::with('distributor','department','venueType','brand','location','eventType')->where('event_location_id',$user->id)->orderBy('id','DESC')->get();
        
        $timezones = app(EventTimezoneService::class);
        foreach ($events as $key => $event) {
            foreach ($timezones->formatEventTimesForApi($event) as $field => $value) {
                $events[$key][$field] = $value;
            }
            
            $getAmbassador = AssignAmbassador::where(['event_id' => $event->id, 'status' => '1'])->pluck('ambassador_id')->toArray();
            if($getAmbassador) {
                $ambData = User::whereIn('id',$getAmbassador)->pluck('name')->toArray();
                $name = implode(",",$ambData);
                $events[$key]['ambassador_name']=$name;
            }
        }
        return $this->successResponse('Data fetched Successfully', [
            'events' =>  $events,
            'markets'  => $markets,
            'managers' => $managers,
            'brands' => $brands,
            'distributors' => $distributors,
            'departments' => $departments,
            'venueTypes' => $venueTypes,
            'locations' => $locations,
            'ambassadors' => $ambassadors,
            'getTotalHoursLocation' => $this->eventService->getTotalHoursLocation()
        ]);
    }
    
    public function locationFilterEvents(Request $request){
        $query = Event::where('event_location_id',Auth::id());
        
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

        // Apply ambassador_email filter
        if ($request->filled('ambassador_email')) {
            $query->where('email', $request->ambassador_email);
        }

        // Apply ambassador_name filter
        if ($request->filled('ambassador_name')) {
            $query->where('email', $request->ambassador_name);
        }
        $events = $query->get();
        
        return $this->successResponse('Events filtered successfully.', [
            'html' => view('location.partials.event-table', compact('events'))->render(),
            'events' => $events,
        ]);
    }
    
    public function getProfileById(Request $request){
        $user = User::where('id', $request->id)->first();
        if ($user) {
            return $this->successResponse('User Profile', $user);
        }
        return $this->successResponse('User Not Found');
    }
    
    public function updateClientProfile(Request $request){
        
        if(isset($request->user_id)){
            $user = User::find($request->user_id);
        }else{
            $user = new User();
        }
        $Validator = Validator::make($request->all(), [
            'first_name'=> ['bail', 'required', 'string', 'max:255'],
            'last_name'=> ['bail', 'required', 'string', 'max:255'],
            'phone'=> ['bail', 'required', 'integer','digits_between:10,12','gt:0'],
            'email'     => ['bail', 'required', 'string', 'email', 'max:255'],
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
            $user->brand_id       =      $request->brand_id ? $request->brand_id : $user->brand_id;
            $user->distributor_id       =     $request->distributor_id ? $request->distributor_id : $user->distributor_id;
            $user->role_id       =     $request->role_id ? $request->role_id : $user->role_id;
            $user->password       =     $request->password ?  Hash::make($request->password) : $user->password;
            $user->email       =     $request->email ? $request->email : $user->email;
            $user->status       =    isset($request->status) ? $request->status : $user->status;
            $user->save();
           
            if(isset($request->user_id)){
                return $this->successResponse('Client Profile updated successfully.', $user);
            }else{
                return $this->successResponse('Client Profile created successfully.', $user);
            }
        } catch (Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
        }
    }
    
    public function getEventlist(Request $request){
        $user = JWTAuth::user();
        if($user){
            $this->eventService->markEndedEventsCompletedForApi();
        }

        $locations = User::select('id', 'name')->where('role_id', 4)->whereNotNull('name')->orderBy('name')->get();
        $markets = Market::get();
        $brands = Brand::get();
        $managers = User::where('role_id', 6)->where('status', '1')->get();
        $ambassadors = User::select('id', 'name', 'email')->where('role_id', 5)->orderBy('name')->get();
                    
        $eventsData = Event::with('brand', 'location', 'eventType', 'client', 'recap','assignAmbassadors','assignAmbassadors.ambassador', 'markets')
                    ->where('event_status', '1')
                    ->orderBy('id', 'DESC')->get();
                    
        $states = EventLocation::orderBy('state')->get();
        $clients = User::where('role_id', 3)->get();
        $venueTypes = Venue::get();
        $departments = EventDepartment::get();
        $distributors = EventDistributor::get();
        
        if($user){
            $events=[];
            if($request->date){
                $data = Event::with('eventType','brand')->orderBy('id', 'DESC')->where('event_location_id',$user->id)->where('event_status', '!=', '2');
                $total_count=Event::with('eventType')->orderBy('id', 'DESC')->where('event_location_id',$user->id)->where('event_status', '!=', '2');
            } else {
                $total_count=Event::with('eventType','brand')->orderBy('id', 'DESC')->where('event_location_id',$user->id);
                $data = Event::with('eventType','brand')->orderBy('id', 'DESC')->where('event_location_id',$user->id);
            }
            
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
            if ($request->filled('venue_id')) {
                $data->where('event_location_id', $request->venue_id);
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
            if ($request->filled('varietals')) {
                $data->where('varietals', 'like', '%' . $request->varietals . '%');
            }
            if ($request->filled('client_id')) {
                $data->where('client_id', $request->client_id);
            }
            if ($request->filled('payment_type')) {
                $data->where('payment_type', $request->payment_type);
            }
            if ($request->filled('range')) {
                $data->where('range', $request->range);
            }
            if ($request->filled('cpr_expiry')) {
                $data->where('cpr_expiry', $request->cpr_expiry);
            }
            if ($request->filled('ambassador_email')) {
                $data->where('ambassador_id', $request->ambassador_email);  
            }
            if ($request->filled('brand_id')) {
                $data->forBrandOrDescendants($request->brand_id);
            }
            if ($request->filled('ambassador_name')) {
                $data->where('ambassador_id', $request->ambassador_name);
            }
            if ($request->filled('region')) {
                $data->where('region', $request->region);
            }
            if ($request->filled('include_nearby_events')) {
                $data->where('include_nearby_events', $request->include_nearby_events);
            }
            if ($request->filled('overview_needed')) {
                $data->where('overview_needed', $request->overview_needed);
            }
            if ($request->filled('not_fully_staffed')) {
                $data->where('not_fully_staffed', $request->not_fully_staffed);
            }
            if ($request->filled('kit_required')) {
                $data->where('kit_required', $request->kit_required);
            }
            if ($request->filled('exclude_cancellations')) {
                $data->where('exclude_cancellations', $request->exclude_cancellations);
            }
            if ($request->filled('event_status')) {
                $data->where('event_status', $request->event_status);
            }
            
            if(isset($request->date) && !empty($request->date)){
                $data->whereBetween('start_time', [
                    $request->date.' 00:00:00',
                    $request->date.' 23:59:59'
                ]);
                $total_count->whereBetween('start_time', [
                    $request->date.' 00:00:00',
                    $request->date.' 23:59:59'
                ]);
            }
            
            $data = $data->get();
            $total_count = $total_count->count();
            
            foreach($data as $key=>$event){
                $html='<ion-icon name="eye"  size="large" color="primary" id="'.$event->id.'"></ion-icon>';
                $events[]=array(
                    'event_name'    => $event->event_name ?? '-',
                    'day'           => $event->Dayname ?? '-',
                    'date'          => $event->FormattedDate ?? '-',
                    'time'          => $event->FormattedTime ?? '-',
                    'brand_name'    => $event->brand->brand_name ?? '-',
                    'distributor'   => $event->distributor?$event->distributor->name:'-',
                    // 'start_time'    => $event->start_time,
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
                'ambassadors'       =>   $ambassadors,
                'events'            =>   $events,
                'states'            =>   $states,
                'clients'           =>   $clients,
                'venueTypes'        =>   $venueTypes,
                'departments'       =>   $departments,
                'distributors'      =>   $distributors,
                'eventsData'        =>   $eventsData
            ]);
        }else{
            return $this->errorResponse('Event Not Found', [], 403);
        }  
    }
    public function getEventDetails(Request $request){
        $user = JWTAuth::user();
        if($user){
            $this->eventService->markEndedEventsCompletedForApi();

            $event = Event::with('distributor','department','venueType','brand','location','eventType','eventAttachments')->where('id',$request->id)->orderBy('id','DESC')->first();
            $recaps = Recap::with('ambassador')->where('event_id',$request->id)->get();
            if($event){
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
}
