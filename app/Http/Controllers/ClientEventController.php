<?php

namespace App\Http\Controllers;

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

class ClientEventController extends Controller
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
        $events = Event::with('distributor','department','venueType','brand','location')->where('client_id',Auth::id())->orderBy('start_time','ASC')->get();

        $getTotalHoursClient = $this->eventService->getTotalHoursClient();

        return view('client.event',compact('events','markets','managers','brands','distributors','departments','departments','venueTypes','locations','ambassadors','getTotalHoursClient'));
    }

    public function clientFilterEvents(Request $request)
    {
        $query = Event::where('client_id', Auth::id());

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
        
        $events = $query->orderBy('start_time', 'ASC')->get();
        
        return response()->json(['html'=>view('client.partials.event-table', compact('events'))->render(),'events'=>$events,'status'=>200]);
    }
}
