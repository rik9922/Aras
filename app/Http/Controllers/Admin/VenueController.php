<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $venues = Venue::orderBy('id','DESC')->get();
    
        return view('admin.venue.index',compact('venues'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.venue.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
        ]);

        Venue::create([
            'name' => $request->name,
            'description' =>  $request->description,
        ]);
        return redirect()->route('admin.venues')->with('success', 'Venue added successfully!');
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
        $venue = Venue::find($id);
        return view('admin.venue.edit',compact('venue'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateVenue(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
        ]);
        
        $id = $request->id;
        $venue = Venue::find($id);
        $venue->name = $request->name;
        $venue->description = $request->description;
        $venue->save();
        return redirect()->route('admin.venues')->with('success', 'Venue updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
