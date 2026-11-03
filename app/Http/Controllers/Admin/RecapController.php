<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Event;
use App\Models\EventDepartment;
use App\Models\EventDistributor;
use App\Models\Market;
use App\Models\Recap;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecapController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $submittedId = Recap::pluck('event_id')->toArray();
        if($request->ajax()) {
            $query = Event::with('recap','location','brand')->whereIn('id',$submittedId);
            
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

            if ($request->filled('region')) {
                $query->where('region', $request->region);
            }
            
            // billto
            if ($request->filled('bill_id')) {
                $query->where('client_id', $request->bill_id);
            }
    
            $events = $query->orderByDesc('id')->paginate(20);

            return response()->json([
                'html' => view('admin.recap.partials.recap-table', compact('events'))->render(),
                'events' => $events,
                'count' => $events->total(),
                'status' => 200,
            ]);
        }
        
        $locations = User::where('role_id', 4)->get();
        $venueTypes = Venue::get();
        $departments = EventDepartment::get();
        $distributors = EventDistributor::get();
        $brands = Brand::get();
        $managers = User::where('role_id',1)->get();
        $ambassadors = User::where('role_id',5)->get();
        $markets = Market::get();
        $events = Event::with('distributor','department','venueType','brand','location')->whereIn('id',$submittedId)->orderBy('id','DESC')->paginate(20);
        $role = Auth::user()->role_id;
        $clients = User::select('id', 'name')->where('role_id', 3)->get();

        $recaps = Recap::with('event','event.brand','event.location')->orderByDesc('id')->paginate(20);

        return view('admin.recap.new_index',compact('recaps', 'events','markets','managers','brands','distributors','departments','departments','venueTypes','locations','ambassadors','role', 'clients'));
    }
    
    public function recapFilter(Request $request)
    {
        $submittedId = Recap::pluck('event_id')->toArray();
        $query = Event::with('recap', 'location', 'brand')->whereIn('id', $submittedId);

        if ((string) Auth::user()->role_id === '3') {
            $query->where('client_id', Auth::id());
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('market_id')) {
            $query->where('market_id', $request->market_id);
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

        if ($request->filled('location_state')) {
            $query->whereHas('location', function ($q) use ($request) {
                $q->where('state_id', $request->location_state);
            });
        }

        if ($request->filled('varietals')) {
            $query->where('varietals', 'like', '%'.$request->varietals.'%');
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

        if ($request->filled('region')) {
            $query->where('region', $request->region);
        }

        if ($request->filled('bill_id')) {
            $query->where('client_id', $request->bill_id);
        }

        if ($request->filled('startDate') && $request->filled('endDate')) {
            $startDate = $request->startDate;
            $endDate = $request->endDate;
            $query->whereBetween('start_time', [$startDate.' 00:00:00', $endDate.' 23:59:59']);
        }

        if ($request->input('overview_needed') == 1) {
            $query->whereHas('recap', function ($q) {
                $q->where('overview_needed', 1);
            });
        }

        $events = $query->orderByDesc('id')->paginate(20);

        return response()->json([
            'html' => view('admin.recap.partials.recap-table', compact('events'))->render(),
            'events' => $events,
            'count' => $events->total(),
            'status' => 200,
        ]);
    }
}
