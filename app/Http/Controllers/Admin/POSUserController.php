<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationMail;
use App\Models\State;
use App\Models\User;
use App\Models\Venue;
use App\Models\EventLocation;
use App\Models\Market;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class POSUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $locations = User::where('role_id', 7)->with('posMarkets')->orderBy('id', 'desc')->paginate(20);

        return view('admin.pos.index', compact('locations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $markets = Market::orderBy('name')->get();

        return view('admin.pos.create', compact('markets'));
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
            'city' => 'required',
            'state'=> 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'market_ids' => 'required|array|min:1',
            'market_ids.*' => 'exists:markets,id',
        ]);

        $user = new User();
        $user->role_id = 7; //POS
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->name = $request->first_name.' '.$request->last_name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->state = $request->state;
        $user->city = $request->city;
        $user->address = $request->address;
        $user->latitude = $request->latitude;
        $user->longitude = $request->longitude;
        $user->password = Hash::make($request->password);
        $user->save();

        $user->posMarkets()->sync($request->market_ids);

        return redirect()->route('admin.pos.user')->with('success', 'POS user added successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $location = User::where('role_id', 7)->with('posMarkets')->findOrFail($id);
        $markets = Market::orderBy('name')->get();

        return view('admin.pos.edit', compact('location', 'markets'));
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
            'latitude' => 'required',
            'longitude' => 'required',
            'market_ids' => 'required|array|min:1',
            'market_ids.*' => 'exists:markets,id',
        ]);

        $id = $request->id;
        $user = User::where('role_id', 7)->findOrFail($id);
        $user->role_id = 7;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->name = $request->first_name.' '.$request->last_name;
        $user->phone = $request->phone;
        $user->state = $request->state;
        $user->city = $request->city;
        $user->address = $request->address;
        $user->latitude = $request->latitude;
        $user->longitude = $request->longitude;
        $user->save();

        $user->posMarkets()->sync($request->market_ids);

        return redirect()->route('admin.pos.user')->with('success', 'POS user updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
