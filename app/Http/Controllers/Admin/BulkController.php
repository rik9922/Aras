<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use App\Services\ClientBudgetService;
use Illuminate\Http\Request;

class BulkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.bulk.index');
    }

    public function preview(Request $request)
    {
        $rawInput = $request->input('notes');
        
        $ids = preg_split('/[\s,]+/', $rawInput, -1, PREG_SPLIT_NO_EMPTY);
    
        $events = Event::whereIn('id', $ids)->get();
    
        return view('admin.bulk.index', [
            'events' => $events,
            'notes' => $rawInput
        ]);
    }
    
    public function cancel(Request $request)
    {
        $ids = explode(',', $request->input('event_ids'));
    
        $ids = array_filter($ids, function($id) {
            return is_numeric($id);
        });
    
        Event::whereIn('id', $ids)->update(['event_status' => '2']);
    
        return redirect()->route('admin.events')->with('success', 'All selected events have been canceled.');
    }
    
    public function update(Request $request, ClientBudgetService $budget)
    {
        $eventsData = $request->input('events', []);
        $warnings = [];

        foreach ($eventsData as $id => $data) {
            $event = Event::find($id);
            if (! $event) {
                continue;
            }

            $newStatus = isset($data['event_status']) ? (string) $data['event_status'] : (string) $event->event_status;

            if ($newStatus === '1' && (string) $event->event_status !== '1') {
                $client = $event->client_id ? User::find($event->client_id) : null;
                if ($client) {
                    $eval = $budget->evaluateMarkCompleted($event, $client);
                    if ($eval['pending']) {
                        $budget->requestBudgetApproval($event, $client);
                        $warnings[] = "Event #{$id}: budget approval requested — left Pending.";
                        $newStatus = '0';
                    } elseif (! $eval['allowed']) {
                        $warnings[] = "Event #{$id}: ".$eval['message'];
                        $newStatus = (string) $event->event_status;
                    }
                }
            }

            $payload = [
                'event_name' => $data['event_name'] ?? $event->event_name,
                'position' => $data['position'] ?? $event->position,
                'start_time' => $data['start_time'] ?? $event->start_time,
                'end_time' => $data['end_time'] ?? $event->end_time,
                'pinata_pay' => isset($data['pinata_pay']) ? 1 : 0,
                'event_status' => $newStatus,
            ];

            if ($newStatus === '2' || ($newStatus === '0' && (string) $event->event_status === '1')) {
                $payload['budget_approval_status'] = null;
                $payload['budget_approval_requested_at'] = null;
                $payload['budget_approval_resolved_by'] = null;
                $payload['budget_approval_resolved_at'] = null;
                $payload['budget_approval_note'] = null;
            }

            $event->update($payload);

            if ($newStatus === '1') {
                $client = $event->client_id ? User::find($event->client_id) : null;
                if ($client) {
                    $budget->notifyThresholdsAfterCompletion($event->fresh(), $client);
                }
            }
        }

        $msg = 'Selected events have been updated successfully.';
        if ($warnings !== []) {
            $msg .= ' Notes: '.implode(' ', $warnings);
        }

        return redirect()->route('admin.events')->with('success', $msg);
    }



 
}
