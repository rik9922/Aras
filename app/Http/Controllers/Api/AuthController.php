<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Exceptions\MissingDwollaProfileDataException;
use App\Services\Dwolla\CustomerService;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\ContactUs;
use App\Mail\SendOTP;
use App\Mail\WelcomeEmail;
use App\Mail\DeleteAccountRequestEmail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Requests\VerifyOTP;
use App\Models\PasswordResetToken;
use App\Models\DeleteAccountRequest;
use App\Models\DeviceDetail;
use Illuminate\Support\Facades\Schema;
use App\Trait\FormatResponseTrait;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\ForgotPassword;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\RegisterUserRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;



class AuthController extends Controller
{
    use FormatResponseTrait;

    public function getRoles()
    {
        $roles = Role::where('id', '!=', 1)->get();
        return $this->successResponse('All Roles', $roles);
    }

    public function register(RegisterUserRequest $request)
    {
        $request->validated();
        try {
            $user = User::create([
                'first_name'    => $request->first_name,
                'last_name'    => $request->last_name,
                'name'       => $request->first_name . ' ' . $request->last_name,
                'role_id'    => 2,
                'email'      => $request->email,
                'zip_code'      => $request->zip_code,
                'password'   => Hash::make($request->password)
            ]);

            $token = JWTAuth::attempt(['email' => $request->email, 'password' => $request->password]);
            $user['token'] = $token;
            return $this->successResponse('User registered successfully', $user);
        } catch (Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
        }
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        // Generate JWT Token.
        try {
            
            $deleteRequest = DeleteAccountRequest::where('email',$data['email'])->first();

            if ($deleteRequest) {
                return $this->errorResponse('Your account deletion request is currently being processed. Our Support Team will be in touch with you shortly','validation_error', Response::HTTP_UNAUTHORIZED, new \stdClass());
            }

            if (!$token = JWTAuth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
                return $this->errorResponse('Invalid! Credentials.', 'invalid_credential', Response::HTTP_UNAUTHORIZED, new \stdClass());
            }

            $user = Auth::user();
            if ((int) $user->role_id === 6 && (string) $user->status !== '1') {
                return $this->errorResponse(
                    'Your account has been deactivated.',
                    'inactive_account',
                    Response::HTTP_UNAUTHORIZED,
                    new \stdClass()
                );
            }

            $user['token'] = $token;
            $user->role;

            $dwollaPaymentSetup = null;
            if ((int) $user->role_id === 5 && ! $user->dwollaCustomer) {
                $customerService = app(CustomerService::class);
                $missingFields = $customerService->getMissingDwollaFields($user);

                if ($missingFields !== []) {
                    $dwollaPaymentSetup = [
                        'required' => true,
                        'missing_fields' => array_values($missingFields),
                    ];
                } else {
                    try {
                        $customerService->create($user);
                        $user->refresh();
                    } catch (MissingDwollaProfileDataException $e) {
                        $dwollaPaymentSetup = [
                            'required' => true,
                            'missing_fields' => array_values($e->missingFields),
                        ];
                    } catch (\Throwable $e) {
                        \Log::warning('Dwolla customer creation failed during API login (non-blocking)', [
                            'user_id' => $user->id,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }
            }

            if ($dwollaPaymentSetup !== null) {
                $user->setAttribute('dwolla_payment_setup', $dwollaPaymentSetup);
            }

            return $this->successResponse('User logged in successfully', $user);
        } catch (JWTException $exception) {

            return $this->errorResponse($exception->getMessage(), 'ERROR', Response::HTTP_UNAUTHORIZED, new \stdClass());
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = JWTAuth::user();
            if ($user && Schema::hasTable('device_details')) {
                DeviceDetail::clearForUser((int) $user->id);
            }

            $token = JWTAuth::getToken();

            // Invalidate the token
            JWTAuth::invalidate($token);
            return $this->successResponse('Successfully logged out.', new \stdClass());
        } catch (JWTException $exception) {
            return $this->errorResponse($exception->getMessage(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
        }
    }

    public function getProfile()
    {
        $authId = Auth::user()->id;
        $user = User::with(['role', 'ambassadorProfile', 'ambassadorLiquorLicense'])->find($authId);

        return $this->successResponse('User Profile', $user);
    }

    public function updateProfile(Request $request)
    {
        $user = User::find(Auth::id());
        if(!$user){
            return $this->errorResponse('Token is invalid', 'invalid_token', Response::HTTP_UNAUTHORIZED, new \stdClass());
        }
        
        $Validator = Validator::make($request->all(), [
            'first_name'  => 'required|string|min:2|min:3',
            'last_name'  => 'nullable|string|min:2|min:3',
        ]);
        if ($Validator->fails()) {
            $errors = $Validator->errors();
            foreach ($errors->all() as $key => $value) {
                return $this->errorResponse(ucfirst($value), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
            }
        }

        try {
            if ($request->hasFile('profile_image')) {
                $image = $request->file('profile_image');
                // Ensure the file is valid
                if ($image->isValid()) {
                    $imagename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    $destination = public_path('profile_images');

                    if ((is_dir($destination) || mkdir($destination, 0755, true)) && is_writable($destination)) {
                        $image->move($destination, $imagename);
                        $user->profile_image = 'profile_images/' . $imagename;
                    } else {
                        $path = $image->storeAs('profile_images', $imagename, 'public');
                        $user->profile_image = 'storage/' . $path;
                    }
                } else {
                    return $this->errorResponse("Invalid File", 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
                }
            }

            $user->first_name =     $request->first_name;
            $user->last_name  =     $request->last_name;
            $user->phone    =     $request->phone ? $request->phone : $user->phone;
            $user->city    =     $request->city ? $request->city : $user->city;
            $user->address    =     $request->has('address') ? $request->input('address') : $user->getRawOriginal('address');
            $user->state    =     $request->state ? $request->state : $user->state;
            $user->state_id  =     $request->filled('state_id') ? $request->state_id : $user->state_id;
            $user->venue_name  =     $request->venue_name ? $request->venue_name : $user->venue_name;
            $user->venue_type  =     $request->venue_type ? $request->venue_type : $user->venue_type;
            $user->name       =     $request->first_name . ' ' . $request->last_name;
            // Preserve scalar IDs exactly as submitted; the previous implementation
            // duplicated each value into a space-separated string.
            if ($request->has('brand_id')) {
                $user->brand_id = is_array($request->input('brand_id'))
                    ? implode(',', $request->input('brand_id'))
                    : $request->input('brand_id');
            }
            if ($request->has('distributor_id')) {
                $user->distributor_id = is_array($request->input('distributor_id'))
                    ? implode(',', $request->input('distributor_id'))
                    : $request->input('distributor_id');
            }
            $user->zip_code = $request->has('zip_code') ? $request->input('zip_code') : $user->getRawOriginal('zip_code');
            $user->status = $request->status ?? $user->status;
            if($request->password) {
                $user->password = Hash::make($request->password);
            }
            $user->save();
            if(isset($user->role_id)){
                $user->role = Role::find($user->role_id);
            }
        
            return $this->successResponse('Profile updated successfully.', $user);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception->getMessage(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
        }
    }


    public function changePassword(Request $request)
    {
        $Validator = Validator::make($request->all(), [
            'current_password'   => 'required|string|min:5',
            'password'   => 'required|string|min:5|confirmed',
        ]);
        if ($Validator->fails()) {
            $errors = $Validator->errors();
            foreach ($errors->all() as $key => $value) {
                return $this->errorResponse(ucfirst($value), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
            }
        }

        $auth = Auth::user();

        // The passwords matches
        if (!Hash::check($request->get('current_password'), $auth->password)) {
            return $this->errorResponse("Current Password is Invalid", 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
        }

        // Current password and new password same
        if (strcmp($request->get('current_password'), $request->password) == 0) {
            return $this->errorResponse("New Password cannot be same as your current password.", 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
        }

        $user =  User::find($auth->id);
        $user->password =  Hash::make($request->password);
        $user->save();
        return $this->successResponse("Password Changed Successfully", $user);
    }

    public function deleteProfile(Request $request)
    {
        $Validator = Validator::make($request->all(), [
            'reason'   => 'required|string',
        ]);
        if ($Validator->fails()) {
            $errors = $Validator->errors();
            foreach ($errors->all() as $key => $value) {
                return $this->errorResponse(ucfirst($value), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
            }
        }
        try {
            
            $user = Auth::user();
            $getEmail = DeleteAccountRequest::where('email', $user->email)->first();
            if ($getEmail) {
                $msg = 'Your Delete Account Request Has Already Been Submitted, Support Team Will Contact You Soon.';
                return $this->errorResponse($msg,'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
            }
            
            // Save delete account request
            $data = new DeleteAccountRequest;
            $data->name = $user->name;
            $data->email = $user->email;
            $data->phone_number = $user->phone;
            $data->reason_for_deletion = $request->reason;
            $data->source = 'Mobile';
            $data->user_id = $user->id;
            $data->status = 0;
            $data->save();
            $adminEmail = User::where('role_id', 1)->first();
            if ($adminEmail) {
                Mail::to($adminEmail->email)->send(new DeleteAccountRequestEmail($data->name, $data->email, $data->reason_for_deletion));
            }

            return $this->successResponse('Your delete account request has been sent successfully.');
            
        } catch (Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
        }
    }

    public function forgotPassword(ForgotPassword $request)
    {
        $data = $request->validated();

        try {
            $oldOTP = PasswordResetToken::where('email', $data['email'])->first();
            if ($oldOTP) {
                PasswordResetToken::where('email', $data['email'])->delete();
            }

            $CODE = rand(1000, 9999);
            PasswordResetToken::create(['email' => $data['email'], 'token' => $CODE, 'verified_at' => null]);
             $res = ['OTP' => $CODE];
            Mail::to($request->email)->send(new SendOTP($CODE, $data['email']));
            // Mail::to('jamtchapp@gmail.com')->send(new SendOTP($CODE, $data['email']));
            return $this->successResponse('Please enter the code that was sent to the email associated with your account.',$res);
        } catch (Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
        }
    }
    public function send_otp(ForgotPassword $request)
    {
        $data = $request->validated();

        try {
            $oldOTP = PasswordResetToken::where('email', $data['email'])->first();
            if ($oldOTP) {
                PasswordResetToken::where('email', $data['email'])->delete();
            }

            $CODE = rand(1000, 9999);
            PasswordResetToken::create(['email' => $data['email'], 'token' => $CODE, 'verified_at' => null]);
             $res = ['OTP' => $CODE];
            Mail::to($request->email)->send(new SendOTP($CODE, $data['email']));
            return $this->successResponse('Please enter the code that was sent to the email associated with your account.',$res);
        } catch (Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
        }
    }

    public function verifyOtp(VerifyOtp $request)
    {
        $data = $request->validated();
        try {
            PasswordResetToken::where(['email' => $data['email'], 'token' => $data['code']])->update(['verified_at' => Carbon::now()]);
            return $this->successResponse('OTP has been verified successfully!', new \stdClass());
        } catch (Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
        }
    }

    public function resetPassword(Request $request)
    {
        $Validator = Validator::make($request->all(), [
            'email'      => 'required|string|email|max:100',
            'password'   => 'required|string|min:5|confirmed',
        ]);
        if ($Validator->fails()) {
            $errors = $Validator->errors();
            foreach ($errors->all() as $key => $value) {
                return $this->errorResponse(ucfirst($value), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
            }
        }

        try {
            $user = User::whereEmail($request['email'])->first();
            $user->update(['password' => Hash::make($request['password'])]);
            PasswordResetToken::where(['email' => $request['email'], 'token' => $request['code']])->delete();
            return $this->successResponse('Your password has been changed, you may now login with your new info.', new \stdClass());
        } catch (Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
        }
    }


    public function getProfileDetailsById($id)
    {
        if (empty(Auth::id())) {
            return $this->errorResponse('Token is invalid', 'invalid_token', Response::HTTP_UNAUTHORIZED, new \stdClass());
        }
        $user = User::with(['role', 'ambassadorProfile', 'ambassadorLiquorLicense'])->find($id);
        if ($user) {
            return $this->successResponse('User Profile', $user);
        }
        return $this->successResponse('User Not Found');
    }



    public function deleteUser($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->delete();
            return $this->successResponse('User Deleted Successfully');
        }
        return $this->successResponse('User Not Found');
    }

    public function contactUs(Request $request)
    {
         $validator = Validator::make($request->all(), [
             'email' => 'required|email|max:255',
             'phone' => 'required',
             'message' => 'required',
         ]);

         if ($validator->fails()) {
             $errors = $validator->errors();
             foreach ($errors->all() as $key => $value) {
                 return $this->errorResponse(ucfirst($value), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
             }
         }

         try {
             $contact = ContactUs::create([
                 'email' => $request->email,
                 'phone' => $request->phone,
                 'message' => $request->message,
             ]);

             return $this->successResponse('Thank you for contacting us. We will get back to you shortly.', $contact);
         } catch (Exception $exception) {
             return $this->errorResponse($exception->getMessage(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
         }
    }
    
    public function userInformationForDeleteAC(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|exists:users,email',
            'phone' => 'nullable',
            'reason' => 'required',
            'verification'=>'required'
        ],[
            'verification.required'=>'OTP Field is Required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            foreach ($errors->all() as $key => $value) {
                return $this->errorResponse(ucfirst($value), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
            }
        }
        try {
            
            $otp = $request->verification;
            $getOTPFromDB = PasswordResetToken::where('email',$request->email)->first();
            if ($getOTPFromDB) {
                if($otp == $getOTPFromDB->token){
                    $existingRequest = DeleteAccountRequest::where('email',$request->email)->first();
                    if($existingRequest){
                        return $this->errorResponse('Your Account Delete Request Already Pending, Our Support Team Contact You Soon.','ERROR',Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
                    }
                    
                    $user = User::where('email',$request->email)->first();
                    
                    $data = new DeleteAccountRequest;
                    $data->name = $user->name;
                    $data->email = $user->email;
                    $data->phone_number = $user->phone ?? $request->phone;
                    $data->reason_for_deletion = $request->reason;
                    $data->source = $request->source;
                    $data->user_id = $user->id;
                    $data->status = 0;
                    $data->save();
                    $adminEmail = User::where('role_id', 1)->first();
                    if ($adminEmail) {
                        Mail::to($adminEmail->email)->send(new DeleteAccountRequestEmail($data->name, $data->email, $data->reason_for_deletion));
                    }
        
                    return $this->successResponse('Your delete account request has been sent successfully.');
                    
                    
                }else{
                    return $this->errorResponse('Incorrect OTP Entered , Please Check Again','ERROR',Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
                }
 
            } else {
                return $this->errorResponse('Incorrect Email Entered , Please Check Again','ERROR',Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
            }
        } catch (Exception $exception) {
             return $this->errorResponse($exception->getMessage(), 'ERROR', Response::HTTP_UNPROCESSABLE_ENTITY, new \stdClass());
        }
    }

    // $this->notifyMessage($sender,$receiverId,$detail,$type,null,null,$bookingId);


}
