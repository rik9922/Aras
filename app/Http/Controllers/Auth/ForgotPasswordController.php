<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use App\Trait\Auth\AuthenticateUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;
    
    
    public function viewForgetPassword(Request $request){
        return view('auth.forgetPassword');
    }
    
    public function viewVerificationCode(Request $request){
        $email = $request->query('email');
        return view('auth.verificationCode',compact('email'));
    }
    
    public function viewResetPassword(Request $request){
        $email = $request->query('email');
        $code = $request->query('code');
        
        $email = urldecode($email);
        $code = urldecode($code);

        return view('auth.resetPassword',compact('email','code'));
    }
    
    
}
