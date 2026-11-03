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
use DB;

class BrandController extends Controller
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
            $events = Event::select(DB::raw('DATE(start_time) as date'),DB::raw('COUNT(*) as count'))->whereRaw('FIND_IN_SET('.$user->id.', brand_id)')
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
    
    public function getEventsCalenderData(Request $request){
        $user = JWTAuth::user();
        if($user){
            $this->eventService->markEndedEventsCompletedForApi();

            $query = Event::query()->forBrandOrDescendants($user->brand?->id);
            
            if ($request->has('date') && $request->date != '') {
               $query->whereDate('booked_date', $request->date);
            }
            $events = $query->get();
            
            $totalMinutes = 0;

            foreach($events as $event) {
                $startTime = Carbon::parse($event->start_time);
                $endTime = Carbon::parse($event->end_time);
                $diffInMinutes = $startTime->diffInMinutes($endTime);
                $totalMinutes += $diffInMinutes;
            }
    
            $hours = floor($totalMinutes / 60);
            $minutes = $totalMinutes % 60;
            
            $totalHours = str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ' Hrs';
            $total_record = $query->count();
            $brandEvents = [];
            $data=$query->offset($request->start)->limit(10)->get();
            $timezones = app(EventTimezoneService::class);
            foreach ($data as $key => $event) {
                foreach ($timezones->formatEventTimesForApi($event) as $field => $value) {
                    $data[$key][$field] = $value;
                }
            }
            
            if(count($data)>0){
                foreach($data as $key=>$val){
                    $brandEvents[]=array(
                        'id'      => $val->id ?? '',
                        'day'      => $val->day ?? '',
                        'start_date'    => $val->booked_date ?? '',
                    );
                }
            }
            return $this->successResponse('Data fetched successfully', [
                "draw"              => (int)$request->draw,
                'data'              => $brandEvents,
                'recordsTotal'      => $total_record,
                'recordsFiltered'   => $total_record,
                'totalHours'        => $totalHours
            ]);
        } else {
            return $this->errorResponse('Event Not Found', [], 403);
        }
    }
    
    public function getEventlist(Request $request){
        $user = JWTAuth::user();
        if($user){
            $this->eventService->markEndedEventsCompletedForApi();

            $events=[];
            if($request->date){
                $data = Event::with('eventType')->whereRaw('FIND_IN_SET('.$user->id.', brand_id)')->whereBetween('start_time', [
                    $request->date.' 00:00:00',
                    $request->date.' 23:59:59'
                ])->where('event_status', '!=', '2')->orderBy('id', 'DESC')->offset($request->start)->limit(10)->get();   
                $total_count=Event::with('eventType')->whereRaw('FIND_IN_SET('.$user->id.', brand_id)')->whereBetween('start_time', [
                    $request->date.' 00:00:00',
                    $request->date.' 23:59:59'
                ])->where('event_status', '!=', '2')->orderBy('id', 'DESC')->count();
            } else {
                $total_count=Event::with('eventType')->whereRaw('FIND_IN_SET('.$user->id.', brand_id)')->orderBy('id', 'DESC')->count();
                $data = Event::with('eventType')->whereRaw('FIND_IN_SET('.$user->id.', brand_id)')->orderBy('id', 'DESC')->offset($request->start)->limit(10)->get();   
            }
            foreach($data as $key=>$event){
                $html='<ion-icon name="eye"  size="large" color="primary" id="'.$event->id.'"></ion-icon>';
                $events[]=array(
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
}
    
