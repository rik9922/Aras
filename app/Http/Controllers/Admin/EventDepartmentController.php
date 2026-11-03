<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventDepartment;
use Illuminate\Http\Request;

class EventDepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */ 
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $search = $request->search;
            $departments = EventDepartment::when($search, function ($query, $search) {
                return $query->where('name', 'like', '%' . $search . '%');
            })
                ->orderBy('name')
                ->orderBy('id')
                ->get();

            return response()->json([
                'departments' => $departments
            ]);
        }

        $departments = EventDepartment::orderBy('name')->orderBy('id')->paginate(20);
        return view('admin.event_departments.index', compact('departments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.event_departments.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the input data
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        // Save the new department data
        EventDepartment::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Event Department created successfully!']);
        }
        $routePrefix = (int) (auth()->user()->role_id ?? 0) === 6 ? 'manager' : 'admin';
        return redirect()->route($routePrefix.'.event-department')->with('success', 'Event Department created successfully!');
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
        $department = EventDepartment::findOrFail($id);
        return view('admin.event_departments.edit', compact('department'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateEventDepartment(Request $request)
    {
        
        // Validate the input data
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $id = $request->id;
        // Find the event department by ID
        $department = EventDepartment::findOrFail($id);

        // Update the department with new data
        $department->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Event Department updated successfully!']);
        }
        $routePrefix = (int) (auth()->user()->role_id ?? 0) === 6 ? 'manager' : 'admin';
        return redirect()->route($routePrefix.'.event-department')->with('success', 'Event Department updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $department = EventDepartment::findOrFail($id);

        if ($department->events()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This Department cannot be deleted because it is associated with existing events.',
            ], 422);
        }

        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully.',
        ]);
    }
}
