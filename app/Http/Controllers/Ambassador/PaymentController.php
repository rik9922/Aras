<?php

namespace App\Http\Controllers\Ambassador;

use App\Http\Controllers\Controller;
use App\Mail\SummitedRecapMail;
use App\Models\AssignAmbassador;
use App\Models\Brand;
use App\Models\Event;
use App\Models\Recap;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $assignAmb = AssignAmbassador::where('ambassador_id', Auth::id())->pluck('event_id')->toArray();
        $submittedId = Recap::where('ambassador_id', Auth::id())->pluck('event_id')->toArray();
        $eventDifference = array_diff($assignAmb, $submittedId);
        
        $wage = Auth::user()->wage;
        
        
        $query = Event::with('brand', 'location', 'eventType', 'client', 'recap','assignAmbassadors')
                      ->whereIn('id', $submittedId)
                      ->where('event_status', '1')
                      ->orderBy('id', 'DESC');

        if ($request->filled('startDate') && $request->filled('endDate')) {
            $startDate = $request->startDate;
            $endDate = $request->endDate;
            $query->whereBetween('start_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }


        $getEventCont = $query->count();
        $recaps = $query->get();
        
        $totalPay = 0;
        $totalMinutes = 0;
        $totalHoursDecimal = 0;
        
        foreach ($recaps as $recap) {
            // Calculate Amount
            if ($recap->assignAmbassadors && $recap->assignAmbassadors->override_pay) {
                $amount = $recap->assignAmbassadors->override_pay + $wage;
            } else {
                $amount = $wage;
            }
            $totalPay += $amount;
        
            // Calculate Time
            if ($recap->start_time && $recap->end_time) {
                $start = \Carbon\Carbon::parse($recap->start_time);
                $end = \Carbon\Carbon::parse($recap->end_time);
                $minutes = $start->diffInMinutes($end); // get total minutes
                $totalMinutes += $minutes;
            }
        }
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        $totalHoursFormatted = str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ' Hours';
    
        if ($request->ajax()) {
            return response()->json([
                'html' => view('ambassador.payment.table', compact('recaps','wage'))->render()
            ]);
        }
    
        return view('ambassador.payment.index', compact('recaps','wage','getEventCont','totalPay','totalHoursFormatted','totalMinutes'));
    }

    

}