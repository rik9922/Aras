<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AdminRecapEvent;
use App\Exports\BrandsExport;
use App\Exports\ClientEventExport;
use App\Exports\ClientRecapEventExport;
use App\Exports\EventInvoicingClientExport;
use App\Exports\EventInvoicingEventExport;
use App\Exports\EventInvoicingMonthExport;
use App\Exports\MonthlyEventsExport;
use App\Http\Controllers\Controller;
use App\Models\Recap;
use App\Services\RecapExportService;
use Illuminate\Http\Request;
use App\Exports\EventsExport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Carbon\Carbon;
class ExportController extends Controller
{
    public function exportEvents(Request $request)
    {
        $filters = $request->only([
            'department_id', 'market_id', 'manager_id', 'event_type_id',
            'distributor_id', 'location_id', 'varietals', 'brand_id',
            'ambassador_name_id', 'ambassador_email_id', 'bill_id', 'payment_type',
            'event_id', 'startDate', 'endDate', 'backup_brand_name', 'location_state',
        ]);

        $booleanFilters = [
            'include_nearby_events',
            'overview_needed',
            'not_fully_staffed',
            'kit_required',
            'exclude_cancellations',
            'backup_brand_used',
        ];

        foreach ($booleanFilters as $filter) {
            $filters[$filter] = $request->has($filter) && $request->input($filter) == 1;
        }

        if ($request->has('event_status') && $request->input('event_status') !== '') {
            $filters['event_status'] = $request->input('event_status');
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        
        return Excel::download(new EventsExport($filters), "events_export_{$timestamp}.xlsx");
    }

    public function exportRecapEvent(Request $request) {
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
            'overview_needed' => $request->input('overview_needed') == 1
        ];
        
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });
    
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        return Excel::download(new AdminRecapEvent($filters), "events_recap_export_{$timestamp}.xlsx");
    }

    /**
     * Email a single-recap Excel export to the currently logged-in user.
     */
    public function emailSingleRecapExport(int $id, RecapExportService $recapExportService)
    {
        $user = Auth::user();
        if (! $user) {
            return back()->with('error', 'You must be logged in to export a recap.');
        }

        $recap = Recap::with(['event.brand', 'event.location'])->findOrFail($id);

        try {
            $this->authorizeRecapExport($recap);
            $recapExportService->emailExportToUser($user, $recap);
        } catch (\Throwable $e) {
            if (! $e instanceof RuntimeException) {
                report($e);
            }

            return back()->with(
                'error',
                $e instanceof RuntimeException
                    ? $e->getMessage()
                    : 'We could not send the recap export email. Please try again or contact support.'
            );
        }

        return back()->with(
            'success',
            'Recap export has been emailed to '.$user->email.'. If you do not see it within a few minutes, check your spam or junk folder.'
        );
    }

    private function authorizeRecapExport(Recap $recap): void
    {
        $user = Auth::user();
        $roleId = (string) ($user->role_id ?? '');

        if (in_array($roleId, ['1', '6'], true)) {
            return;
        }

        if ($roleId === '3') {
            $event = $recap->event;
            if ($event && (int) $event->client_id === (int) $user->id) {
                return;
            }

            throw new RuntimeException('You are not authorized to export this recap.');
        }

        throw new RuntimeException('You are not authorized to export this recap.');
    }

    public function clientExportEvent(Request $request) {
        
        $filters = [
            'department_id' => $request->input('department_id'),
            'market_id' => $request->input('market_id'),
            'event_type_id' => $request->input('venue_id'),
            'distributor_id' => $request->input('distributor_id'),
            'location_id' => $request->input('location_id'),
            'varietals' => $request->input('varietals'),
            'brand_id' => $request->input('brand_id'),
            'ambassador_name_id' => $request->input('ambassador_name_id'),
            'ambassador_email_id' => $request->input('ambassador_email_id'),
            'payment_type' => $request->input('payment_type'),
            'event_id' => $request->input('event_id')
        ];
        
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });
    
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        return Excel::download(new ClientEventExport($filters), "client_events_export_{$timestamp}.xlsx");
    }

    public function exportMonthlyEvents(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'client_id' => 'nullable|integer',
        ]);

        $month = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $clientId = $request->input('client_id') ? (int) $request->input('client_id') : null;

        $timestamp = now()->format('Y-m-d_H-i-s');

        return Excel::download(
            new MonthlyEventsExport($start, $end, $clientId),
            "monthly_events_{$request->month}_{$timestamp}.xlsx"
        );
    }

    /**
     * Event invoicing (prepare) export: one row per supplier/customer per event with amounts.
     */
    public function exportEventInvoicingMonth(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'client_id' => 'nullable|integer',
        ]);

        $month = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $clientId = $request->input('client_id') ? (int) $request->input('client_id') : null;

        $timestamp = now()->format('Y-m-d_H-i-s');

        return Excel::download(
            new EventInvoicingMonthExport($start, $end, $clientId),
            "event_invoicing_{$request->month}_{$timestamp}.xlsx"
        );
    }

    public function exportEventInvoicingEvent(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $month  = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
        $start  = $month->copy()->startOfMonth();
        $end    = $month->copy()->endOfMonth();
        $search       = $request->filled('search') ? $request->input('search') : null;
        $clientSearch = $request->filled('client_search') ? $request->input('client_search') : null;
        $timestamp    = now()->format('Y-m-d_H-i-s');

        return Excel::download(
            new EventInvoicingEventExport($start, $end, $search, $clientSearch),
            "event_invoicing_event_{$request->month}_{$timestamp}.xlsx"
        );
    }

    public function exportEventInvoicingClient(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $month        = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
        $start        = $month->copy()->startOfMonth();
        $end          = $month->copy()->endOfMonth();
        $clientSearch = $request->filled('client_search') ? $request->input('client_search') : null;
        $timestamp    = now()->format('Y-m-d_H-i-s');

        return Excel::download(
            new EventInvoicingClientExport($start, $end, $clientSearch),
            "event_invoicing_client_{$request->month}_{$timestamp}.xlsx"
        );
    }

    public function exportBrands(Request $request)
    {
        $search = $request->filled('search') ? (string) $request->input('search') : null;
        $brandCategoryId = $request->filled('brand_category_id')
            ? (int) $request->input('brand_category_id')
            : null;

        $timestamp = now()->format('Y-m-d_H-i-s');

        return Excel::download(
            new BrandsExport($search, $brandCategoryId),
            "brands_export_{$timestamp}.xlsx"
        );
    }
}
