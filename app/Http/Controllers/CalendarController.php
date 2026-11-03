<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AssignAmbassador;
use App\Models\Event;
use App\Models\User;
use App\Services\EventTimezoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function adminCalendar(EventTimezoneService $timezones)
    {
        $events = Event::with('brand')
            ->select('id', 'event_name', 'brand_id', 'start_time', 'end_time', 'timezone', 'timezone_label', 'position', 'kit', 'age_restricted', 'ambassador_id', 'event_status')
            ->where('event_status', '!=', '2') // Exclude cancelled — badge and list show Pending + Completed only
            ->get()
            ->map(fn (Event $event) => $timezones->calendarEventPayload($event));

        return view('admin.event.calendar', [
            'events' => $events,
            'role' => auth()->user()->role_id,
        ]);
    }

    public function managerCalendar(EventTimezoneService $timezones)
    {
        $events = Event::with('brand')
            ->select('id', 'event_name', 'brand_id', 'start_time', 'end_time', 'timezone', 'timezone_label', 'position', 'kit', 'age_restricted', 'ambassador_id', 'event_status')
            ->where('manager_id', Auth::id())
            ->where('event_status', '!=', '2') // Exclude cancelled — badge and list show Pending + Completed only
            ->get()
            ->map(fn (Event $event) => $timezones->calendarEventPayload($event));

        return view('admin.event.calendar', [
            'events' => $events,
            'role' => auth()->user()->role_id,
        ]);
    }

    public function calendar(EventTimezoneService $timezones)
    {
        // Exclude cancelled events for all roles — badge and list show Pending + Completed only
        if (Auth::user()->role_id == 5) {
            $assignAmb = AssignAmbassador::where('ambassador_id', Auth::id())->pluck('event_id')->toArray();
            $query = Event::select('id', 'start_time', 'end_time', 'event_name', 'timezone', 'timezone_label', 'kit', 'age_restricted', 'ambassador_id', 'event_status')->whereIn('id', $assignAmb)->where('event_status', '!=', '2');
        } elseif (Auth::user()->role_id == 3) {
            $query = Event::select('id', 'start_time', 'end_time', 'event_name', 'timezone', 'timezone_label', 'kit', 'age_restricted', 'ambassador_id', 'event_status')->where('client_id', Auth::id())->where('event_status', '!=', '2');
        } elseif (Auth::user()->role_id == 4) {
            $query = Event::select('id', 'start_time', 'end_time', 'event_name', 'timezone', 'timezone_label', 'kit', 'age_restricted', 'ambassador_id')->where('event_location_id', Auth::id())->where('event_status', '!=', '2');
        } else {
            $query = Event::select('id', 'start_time', 'end_time', 'event_name', 'timezone', 'timezone_label', 'kit', 'age_restricted', 'ambassador_id')->whereRaw('FIND_IN_SET(' . Auth::id() . ', brand_id)')->where('event_status', '!=', '2');
        }

        $events = $query->get()->map(fn (Event $event) => $timezones->calendarEventPayload($event));
        $role = Auth::user()->role_id;
        $quickbooksRequirements = $this->quickBooksRecapRequirements(Auth::user());

        return view('common.calendar', compact('events', 'role', 'quickbooksRequirements'));
    }

    private function quickBooksRecapRequirements(?User $user): array
    {
        if (! $user || (string) ($user->role_id ?? '') !== '5') {
            return [
                'show' => false,
                'ready' => true,
                'w9_completed' => true,
                'quickbooks_1099_completed' => true,
            ];
        }

        $user->loadMissing('w9Form');
        $w9Form = $user->w9Form;

        $w9Completed = (bool) $w9Form;
        $quickbooksVendorId = $w9Form?->quickbooks_vendor_id ?? $user->quickbooks_vendor_id ?? null;
        $quickbooks1099Completed = $w9Completed
            && ! empty($quickbooksVendorId)
            && (bool) ($w9Form?->is_1099_vendor ?? false);

        return [
            'show' => true,
            'ready' => $w9Completed && $quickbooks1099Completed,
            'w9_completed' => $w9Completed,
            'quickbooks_1099_completed' => $quickbooks1099Completed,
        ];
    }
}
