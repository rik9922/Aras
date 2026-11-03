<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventDistributorRequest;
use App\Models\EventDistributor;
use Illuminate\Http\Request;

class EventDistributorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = EventDistributor::query();

            if($request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%$search%");
                });
            }

            $eventDistributor = $query
                ->orderBy('name')
                ->orderBy('id')
                ->get();
            return response()->json([
                'eventDistributors' => $eventDistributor
            ]);
        }
        
        $eventDistributor = EventDistributor::orderBy('name')->orderBy('id')->paginate(20);
        return view('admin.event-distributor.index',compact('eventDistributor'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.event-distributor.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EventDistributorRequest $request)
    {
        $validatedData = $request->validated();
        EventDistributor::create($validatedData);
        
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Event Distributor added successfully!']);
        }
        $routePrefix = (int) (auth()->user()->role_id ?? 0) === 6 ? 'manager' : 'admin';
        return redirect()->route($routePrefix.'.event-destributor')->with('success', 'Event Distributor added successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $eventDistributor = EventDistributor::find($id);
        return view('admin.event-distributor.edit',compact('eventDistributor'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateEventDistributor(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $eventDistributor = EventDistributor::find($request->id);

        $routePrefix = (int) (auth()->user()->role_id ?? 0) === 6 ? 'manager' : 'admin';
        if (!$eventDistributor) {
            return redirect()->route($routePrefix.'.event-destributor')->with('error', 'EventDistributor not found');
        }

        // Fill the model with the validated data
        $eventDistributor->fill([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'company_name' => $request->company_name,
            'address' => $request->address,
            'type' => $request->type,
        ]);

        $eventDistributor->save();
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'EventDistributor updated successfully']);
        }
        $routePrefix = (int) (auth()->user()->role_id ?? 0) === 6 ? 'manager' : 'admin';
        return redirect()->route($routePrefix.'.event-destributor')->with('success', 'EventDistributor updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $distributor = EventDistributor::findOrFail($id);

        if ($distributor->events()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This Distributor cannot be deleted because it is associated with existing events.',
            ], 422);
        }

        $distributor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Distributor deleted successfully.',
        ]);
    }
}
