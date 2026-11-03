<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::where('role_id', 2)->get();

        return view('admin.user.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.user.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|min:2|min:3',
            'last_name' => 'nullable|string|min:2|min:3',
            'email' => ['required', 'email:rfc,dns', Rule::unique('users', 'email')->where(fn ($query) => $query->whereIn('role_id', [2, 6]))],
            'zip_code' => 'required',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $user = User::create([
                'role_id' => 2,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'name' => $request->first_name.' '.$request->last_name,
                'email' => $request->email,
                'zip_code' => $request->zip_code,
                'password' => Hash::make($request->password),
            ]);

            if (! empty($result['customer_id'])) {
                $users = User::find($user->id);
                $users->dwolla_customer_id = $result['customer_id'];
                $users->save();
            }

            flash()->success('User registered successfully');

            return redirect()->route('users.index');
        } catch (Exception $exception) {
            flash()->error($exception->getMessage());

            return redirect()->route('user.create');
        }
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
    public function edit(string $id)
    {
        $user = User::find($id);

        return view('admin.user.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $user = User::find($id);
        if (! $user) {
            flash()->error('User not found');

            return redirect()->route('users.index');
        }
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|min:2|min:3',
            'last_name' => 'nullable|string|min:2|min:3',
            'email' => ['required', 'email:rfc,dns', Rule::unique('users', 'email')->where(fn ($query) => $query->whereIn('role_id', [2, 6]))->ignore($user)],
            'zip_code' => 'required',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->name = $request->first_name.' '.$request->last_name;
            $user->email = $request->email;
            $user->zip_code = $request->zip_code;
            $user->password = $request->password ? Hash::make($request->password) : $user->password;
            $user->save();

            flash()->success('User details updated successfully');

            return redirect()->route('users.index');
        } catch (Exception $exception) {
            flash()->error($exception->getMessage());

            return redirect()->route('users.edit', $id);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $user = User::find($id);
            if ($user) {
                $user->delete(); // Delete the user
            }
            flash()->success('User has been deleted successfully!');

            return redirect()->route('users.index');
        } catch (Exception $exception) {
            flash()->error($exception->getMessage());

            return redirect()->route('users.index');
        }
    }
}
