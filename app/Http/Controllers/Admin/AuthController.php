<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\ContactUs;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function adminProfile()
    {
        $authId = Auth::user()->id;
        $user = User::find($authId);
        return view('admin.auth.admin_profile', compact('user'));
       
        
    }
    public function update(Request $request, string $id)
    {
        
        $user = User::find($id);
        $validator = Validator::make($request->all(), [
            'first_name'  => 'required|string|min:2|max:100',
            'last_name'  => 'nullable|string|min:2|max:100',
            'email'      => ['required', 'email:rfc,dns', Rule::unique('users')->ignore($user)],
            'address'   => 'required|string',
            'phone'   => 'required|string',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $user->first_name      =     $request->first_name;
            $user->last_name       =     $request->last_name;
            $user->name            =     $request->first_name . ' ' . $request->last_name;
            $user->email           =     $request->email;
            $user->phone           =     $request->phone;
            $user->address         =     $request->address;
            //$user->password   =     $request->password ? Hash::make($request->password) : $user->password;

            $user->save();
            flash()->success('Admin details updated successfully');
            return redirect()->route('admin.events');
        } catch (Exception $exception) {
            flash()->error($exception->getMessage());
            return redirect()->route('admin.profile');
        }
    }

    public function adminPassword()
    {
        $authId = Auth::user()->id;
        $user = User::find($authId);
       return view('admin.auth.admin_change_password', compact('user'));
       
        
    }
    public function updatePassword(Request $request, string $id)
    { 
        $user = User::find($id);
        if (!$user) {
            flash()->error('User not found.');
            return redirect()->route('admin.change-password');
        }
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'current_password:web'],
            'password'         => [ 'required','confirmed'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        // The passwords matches
        if (!Hash::check($request->get('current_password'), $user->password)) {
            flash()->error('Current Password is Invalid.');
            return redirect()->route('admin.change-password');
        }
        // Current password and new password same
        if (strcmp($request->get('current_password'), $request->password) == 0) {
            flash()->error('New Password cannot be same as your current password.');
            return redirect()->route('admin.change-password');
        }
        try {
            $user->password = Hash::make($request->password);
            $user->save();
            flash()->success('Password updated successfully.');
            return redirect()->route('admin.events');
        } catch (Exception $exception) { 
            flash()->error('An error occurred while updating the password: ' . $exception->getMessage());
            return redirect()->route('admin.change-password');
        }
    }
    
    public function contactUs()
    {
       $contacts = ContactUs::get();
       return view('admin.contactUs', compact('contacts'));
       
        
    }
    
    public function deleteContactUs(string $id)
    {
            try {
                $contactdata = ContactUs::find($id);
                if ($contactdata) {
                    $contactdata->delete(); // Delete Contact Data
                }
                flash()->success('Contact request has been deleted successfully!');
                return redirect()->route('contact-us');
            } catch (Exception $exception) {
                flash()->error($exception->getMessage());
                return redirect()->route('contactUs');
            }
    }
    
}
