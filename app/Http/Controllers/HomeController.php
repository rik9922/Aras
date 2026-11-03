<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('admin.event.events');
        // return view('dashboard');
    }

    public function brand()
    {
        return view('admin.brand.brands');
        // return view('dashboard');
    }
    
    
    public function getAuthAccount()
    {
        $user = Auth::user();
        return view('auth.account', compact('user'));
    }

    public function updateAuthAccount(Request $request, $id)
    {
        $user = User::find($id);
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|min:3',
            'last_name' => 'nullable|string|min:3',
            'zip_code' => 'required',
            'email' =>
            [
                'required',
                'string',
                'email',
                'max:255',
                'email:rfc,dns',

            ],
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $user->first_name =     $request->first_name;
            $user->last_name  =     $request->last_name;
            $user->name       =     $request->first_name . ' ' . $request->last_name;
            $user->zip_code = $request->zip_code;
            $user->email = $request->email;

            if ($request->profile_image) {
                $image = $request->file('profile_image');
                // Ensure the file is valid
                if ($image->isValid()) {
                    $imagename = time() . '.' . $image->getClientOriginalExtension();
                    $image->move('profile_images', $imagename);
                    $user->profile_image = '/profile_images/' . $imagename;
                } else {
                    return $this->errorResponse("Invalid File", 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
                }
            }
            
            $user->save();
            flash()->success("Profile Updated Successfully");
            return redirect()->route('admin.dashoard');
        } catch (Exception $exception) {
            flash()->error($exception->getMessage());
            return redirect()->route('admin.dashoard');
        }
    }

    public function changePassword()
    {
        return view('auth.changePassword');
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|min:5',
            'password' => 'required|string|min:5|confirmed',
        ]);
        if ($validator->fails()) {
            // dd($validator);
            return back()->withErrors($validator)->withInput();
        }
        try {
            $auth = Auth::user();

            // The passwords matches
            if (!Hash::check($request->get('current_password'), $auth->password)) {
                return back()->withErrors(['current_password' => "Current password is invalid"])->withInput();
            }
            // Current password and new password same
            if (strcmp($request->get('current_password'), $request->password) == 0) {
                return back()->withErrors(['password' => "New password cannot be same as your current password."])->withInput();
            }

            $user = User::find($auth->id);
            $user->password = Hash::make($request->password);
            $user->save();
            flash()->success("Password Changed Successfully");
            return redirect()->route('admin.dashoard');
        } catch (Exception $exception) {
            flash()->error($exception->getMessage());
            return redirect()->route('admin.dashoard');
        }
    }

}
