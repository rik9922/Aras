<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ManagerCotroller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = User::where('role_id', 6)->with('managerMarkets'); // keep role_id consistent
    
            if (!empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%$search%")
                        ->orWhere('last_name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%")
                        ->orWhere('phone', 'like', "%$search%");
                });
            }
    
            if ($request->inactive !== null && $request->inactive !== '') {
                $query->where('status', $request->inactive);
            }
    
            $managers = $query->orderBy('id', 'DESC')->get();
    
            return response()->json([
                'html' => view('admin.manager.manager_table', compact('managers'))->render()
            ]);
        }
    
        $managers = User::where('role_id', 6)->with('managerMarkets')->orderBy('id', 'DESC')->get();
        return view('admin.manager.index', compact('managers'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $markets = Market::orderBy('name')->get();

        return view('admin.manager.create', compact('markets'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->where(fn ($query) => $query->whereIn('role_id', [2, 6]))],
            'phone' => 'required|string|max:15',
            'address' => 'required',
            'password' => 'required|min:6',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
            'market_ids' => 'nullable|array',
            'market_ids.*' => 'exists:markets,id',
        ]);

        $user = new User();
        $user->role_id = 6;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->name = $request->first_name.' '.$request->last_name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->address = $request->address;
        $user->latitude = $request->latitude;
        $user->longitude = $request->longitude;
        $user->status = $request->status == "1" ? "1" : "0";
        $user->password = Hash::make($request->password);
        $user->save();

        $user->managerMarkets()->sync($request->input('market_ids', []));

        return redirect()->route('admin.managers')->with('success', 'Manager added successfully!');
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
        $manager = User::where('role_id', 6)->with('managerMarkets')->findOrFail($id);
        $markets = Market::orderBy('name')->get();

        return view('admin.manager.edit', compact('manager', 'markets'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateManager(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'address' => 'required',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
            'market_ids' => 'nullable|array',
            'market_ids.*' => 'exists:markets,id',
        ]);

        $id = $request->id;

        $user = User::where('role_id', 6)->findOrFail($id);
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->name = $request->first_name.' '.$request->last_name;
        $user->email = $user->email;
        $user->phone = $request->phone;
        $user->address = $request->address;
        $user->latitude = $request->latitude;
        $user->longitude = $request->longitude;
        $user->status = $request->status == "1" ? "1" : "0";
        $user->save();

        $user->managerMarkets()->sync($request->input('market_ids', []));

        return redirect()->route('admin.managers')->with('success', 'Manager updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
