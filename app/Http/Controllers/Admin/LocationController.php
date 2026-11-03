<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationMail;
use App\Models\State;
use App\Models\User;
use App\Models\Venue;
use App\Models\EventLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = User::where('role_id', 4);
            $search = $request->search;
            
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%");
            });

            if($request->inactive == '0') {
                $query->where('status', $request->inactive);
            }
        
            $locations = $query->get();
            return response()->json([
                'html' => view('admin.location.location_table', compact('locations'))->render()
            ]);
        }
        
        $locations = User::where('role_id',4)->paginate(20);
        return view('admin.location.index',compact('locations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $states = State::get();
        $venues  = Venue::get();
        $eventLocation = EventLocation::get();
        return view('admin.location.create',compact('states','venues','eventLocation'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:15',
            'event_location_id' => 'required',
            'city' => 'required',
            'venue_type' => 'required',
            'venue_name' => 'required',
            'state'=> 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        $user = new User();
        $user->role_id = 4;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->name = $request->first_name.' '.$request->last_name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->status = 1;
        $user->state = $request->state;
        $user->city = $request->city;
        $user->address = $request->address;
        $user->venue_name = $request->venue_name;
        $user->venue_type = $request->venue_type;
        $user->latitude = $request->latitude;
        $user->longitude = $request->longitude;
        $user->event_location_id = $request->event_location_id;
        $user->password = Hash::make($request->password);
        $user->save();

        $data = [
            'name' => $user->name,
            'role' => 'Ambassador',
            'phone' => $user->phone,
            'email' => $user->email,
            'address' => $user->address,
            'password' => $request->password,
        ];

        Mail::to('user1@jtplapps.in')->send(new RegistrationMail($data));

        return redirect()->route('admin.locations')->with('success', 'Location added successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $location = User::findOrFail($id);
        $states = State::get();
        $venues  = Venue::get();
        $eventLocation = EventLocation::get();
        return view('admin.location.edit', compact('location','states','venues','eventLocation'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateLocation(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'state' => 'required',
            'city' => 'required',
            'venue_type' => 'required',
            'venue_name' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'event_location_id' => 'required',
        ]);

        $id = $request->id;
        $user = User::find($id);
        $user->role_id = 4;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->name = $request->first_name.' '.$request->last_name;
        $user->phone = $request->phone;
        $user->status = $request->status;
        $user->state = $request->state;
        $user->city = $request->city;
        $user->address = $request->address;
        $user->event_location_id = $request->event_location_id;
        $user->venue_name = $request->venue_name;
        $user->venue_type = $request->venue_type;
        $user->latitude = $request->latitude;
        $user->longitude = $request->longitude;
        $user->save();

        $data = [
            'name' => $user->name,
            'role' => 'Location',
            'phone' => $user->phone,
            'email' => $user->email,
            'address' => $user->address,
            'password' => $request->password ?? '',
        ];

        Mail::to('user1@jtplapps.in')->send(new RegistrationMail($data));

        return redirect()->route('admin.locations')->with('success', 'Location updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
