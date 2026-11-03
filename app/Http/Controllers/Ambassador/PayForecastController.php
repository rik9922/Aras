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

class PayForecastController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    
    public function index(Request $request)
    {
        $userId = Auth::id();
        $assignAmb = AssignAmbassador::where('ambassador_id', $userId)->pluck('event_id')->toArray();
        $submittedId = Recap::where('ambassador_id', $userId)
            ->where('is_draft', false)
            ->pluck('event_id')
            ->toArray();
        $eventDifference = array_diff($assignAmb, $submittedId);

        $wage = Auth::user()->wage;

        // Bug 8 (MC cmon1a7u4005d3r1h5cdct2vh): Pay Forecast was only listing
        // events with a submitted recap AND event_status=1, so events the
        // ambassador was assigned to but had not yet recapped (or that the
        // manager hadn't marked complete yet) silently disappeared from the
        // forecast — which is exactly the case the testers reported. Pay
        // Forecast is a *forecast*; show every event the BA is assigned to,
        // regardless of event status. Keep ordering newest-first.
        $eventIds = array_values(array_unique(array_merge($assignAmb, $submittedId)));

        $query = Event::with('brand', 'location', 'eventType', 'client', 'recap', 'assignAmbassadors')
                      ->whereIn('id', $eventIds)
                      ->orderBy('start_time', 'DESC')
                      ->orderBy('id', 'DESC');

        // Load the current ambassador's assignment row for every event so
        // the Late column can compare event_date_time vs start_time + grace.
        $graceMinutes = (int) config('attendance.late_checkin_grace_minutes', 10);
        $assignmentByEventId = AssignAmbassador::where('ambassador_id', $userId)
            ->whereIn('event_id', $eventIds)
            ->get()
            ->keyBy('event_id');
    
        if ($request->filled('startDate') && $request->filled('endDate')) {
            $startDate = $request->startDate;
            $endDate = $request->endDate;
            $query->whereBetween('start_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }
    
        $getEventCont = $query->count();
        $recaps = $query->get();

        $recapStatusByEventId = Recap::where('ambassador_id', $userId)
            ->whereIn('event_id', $eventIds)
            ->get(['event_id', 'is_draft', 'is_approved', 'denied_at', 'deny_reason', 'status'])
            ->mapWithKeys(function ($recap) {
                $raw = is_string($recap->status ?? null) ? strtolower(trim($recap->status)) : null;

                $status =
                    $recap->is_draft ? 'Draft'
                    : ((!empty($recap->denied_at) || !empty($recap->deny_reason)) ? 'Rejected'
                        : ($recap->is_approved ? 'Approved' : 'Submitted'));

                if (in_array($raw, ['draft', 'submitted', 'approved', 'rejected'], true)) {
                    $status = ucfirst($raw);
                }

                return [$recap->event_id => $status];
            });
    
        $totalMinutes = 0;
        $totalOverridePay = 0;
    
        foreach ($recaps as $recap) {
            // Calculate Time
            if ($recap->start_time && $recap->end_time) {
                $start = \Carbon\Carbon::parse($recap->start_time);
                $end = \Carbon\Carbon::parse($recap->end_time);
                $minutes = $start->diffInMinutes($end);
                $totalMinutes += $minutes;
            }
    
            // Sum override pay
            $overridePay = optional($recap->assignAmbassadors)->override_pay;
            if (!empty($overridePay) && is_numeric($overridePay)) {
                $totalOverridePay += (float)$overridePay;
            }
        }
    
        // Wage-based pay calculation
        $hoursWorked = floor($totalMinutes / 60);
        $extraMinutes = $totalMinutes % 60;
    
        $perMinuteWage = $wage / 60; // wage per minute
        $wagePay = ($hoursWorked * $wage) + ($extraMinutes * $perMinuteWage);
    
        // Final total pay
        $totalPay = round($wagePay + $totalOverridePay, 2); // round to 2 decimal places
    
        // Format time display
        $totalHoursFormatted = str_pad($hoursWorked, 2, '0', STR_PAD_LEFT) . ':' . str_pad($extraMinutes, 2, '0', STR_PAD_LEFT) . ' Hours';
    
        if ($request->ajax()) {
            return response()->json([
                'html' => view('ambassador.pay.table', compact('recaps', 'wage', 'recapStatusByEventId', 'assignmentByEventId', 'graceMinutes'))->render()
            ]);
        }
    
        return view('ambassador.pay.index', compact('recaps', 'wage', 'getEventCont', 'totalPay', 'totalHoursFormatted', 'totalMinutes', 'recapStatusByEventId', 'assignmentByEventId', 'graceMinutes'));
    }


    

}
