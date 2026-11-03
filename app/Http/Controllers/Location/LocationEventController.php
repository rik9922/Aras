<?php

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Event;
use App\Models\EventDepartment;
use App\Models\EventDistributor;
use App\Models\Market;
use App\Models\User;
use App\Models\Venue;
use App\Services\EventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocationEventController extends Controller
{
    protected $eventService;
    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    public function index() {
        $locations = User::where('role_id', 4)->get();
        $venueTypes = Venue::get();
        $departments = EventDepartment::get();
        $distributors = EventDistributor::get();
        $brands = Brand::get();
        $managers = User::where('role_id',6)->get();
        $ambassadors = User::where('role_id',5)->get();
        $markets = Market::get();
        // Previous requirement (chronological ascending — kept for easy restore):
        // $events = Event::with('distributor','department','venueType','brand','location')->where('event_location_id',Auth::id())->orderBy('start_time','ASC')->get();
        // Latest client/PM requirement — descending by event date (farthest/newest first):
        $events = Event::with('distributor','department','venueType','brand','location')->where('event_location_id',Auth::id())->orderBy('start_time','DESC')->get();

        $getTotalHoursLocation = $this->eventService->getTotalHoursLocation();

        return view('location.event',compact('events','markets','managers','brands','distributors','departments','departments','venueTypes','locations','ambassadors','getTotalHoursLocation'));
    }

    public function locationFilterEvents(Request $request)
    {
        $query = Event::where('event_location_id',Auth::id());
        
        // Apply department filter
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        
        // Apply market filter
        if ($request->filled('market_id')) {
            $query->where('market_id', $request->market_id);
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
        // Previous requirement (chronological ascending — kept for easy restore):
        // $events = $query->orderBy('start_time', 'ASC')->get();
        // Latest client/PM requirement — descending by event date (farthest/newest first):
        $events = $query->orderBy('start_time', 'DESC')->get();
        
        return response()->json(['html'=>view('location.partials.event-table', compact('events'))->render(),'events'=>$events,'status'=>200]);
    }
}
