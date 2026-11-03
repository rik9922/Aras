<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventType;
use App\Services\RecapFormService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventTypeController extends Controller
{
    public function index(Request $request)
    {
        $AmbassadorType = EventType::orderBy('id','DESC')->get();
        return view('admin.event_type.index',compact('AmbassadorType'));
    }

    public function create()
    {
        return view('admin.event_type.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:event_types,name',
            'description' => 'required|string|max:255',
            'recap_type' => ['nullable', Rule::in(RecapFormService::eventTypeRecapTypes())],
        ]);

        EventType::create($request->only(['name', 'description', 'recap_type']));
        return redirect()->route('admin.eventtypes')->with('success', 'Event Type Added Successfully.');
    }

    public function show(EventType $market)
    {
        return $market;
    }


    public function edit($id)
    {
        $AmbassadorType = EventType::find($id);
        return view('admin.event_type.edit',compact('AmbassadorType'));
    }

    public function updateAmbassadorType(Request $request)
    {
        $id = $request->id;
        $request->validate([
            'name' => 'required|string|unique:event_types,name,' . $id,
            'description' => 'required|string|max:255',
            'recap_type' => ['nullable', Rule::in(RecapFormService::eventTypeRecapTypes())],
        ]);

        $id = $request->id;
        $type = EventType::find($id);
        $type->update($request->only(['name', 'description', 'recap_type']));
        return redirect()->route('admin.eventtypes')->with('success', 'Event Type updated Successfully.');
    }

    public function destroy(EventType $market)
    {
        $market->delete();
        return response()->json(['message' => 'Event Type deleted successfully']);
    }
}
