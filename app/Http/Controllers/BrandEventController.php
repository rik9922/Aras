<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\BrandCategory;
use App\Models\Event;
use App\Services\EventService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BrandEventController extends Controller
{
    protected $eventService;
    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }
    public function index(Request $request) {
        $brandId = Auth::user()->brand?->id;
        $events = Event::with('distributor','department','venueType','brand','location')
            ->forBrandOrDescendants($brandId)
            ->orderBy('id','DESC')
            ->paginate(20);
        
        $totalBrandEventHours = $this->eventService->brandTotalEventHour();
        return view('brand.index',compact('events','totalBrandEventHours'));
    }

    public function getEvents(Request $request){
        $brandId = Auth::user()->brand?->id;
        $query = Event::query()->forBrandOrDescendants($brandId);
 
        if ($request->has('date') && $request->date != '') {
            $query->whereDate('booked_date', $request->date);
        }
        
        $events = $query->get();
        
        foreach ($events as $event) {
            $event->Dayname = date('l', strtotime($event->booked_date));
            $event->FormattedDate = date('m/d/Y', strtotime($event->booked_date));
            $event->FormattedTime = $event->start_time . ' - ' . $event->end_time;
        }
        return response()->json(['events' => $events]);
    }
}
