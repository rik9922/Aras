<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\DeleteAccountRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmationOfDeleteRequestAccount;

class DeleteAccountRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pendingRequest = DeleteAccountRequest::where('status',0)->get();
        return view('admin.deleteRequest.index', compact('pendingRequest'));
    }


    /**
     * Remove the specified resource from storage.
     */
    public function rejectRequest(string $id)
    {
        try {
            $deleteRequest = DeleteAccountRequest::find($id);
            
            if($deleteRequest){
                // Send the Delete Request Accept
                Mail::to($deleteRequest->email)->send(new ConfirmationOfDeleteRequestAccount($deleteRequest->name, 'rejected'));
                // delete  deleteAccountRequest
                $deleteRequest->delete();
            }
            flash()->success('Delete Request Rejected Successfully!');
            return redirect()->route('delete-request.index');
        } catch (Exception $exception) {
            flash()->error($exception->getMessage());
            return redirect()->route('delete-request.index');
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $deleteRequest = DeleteAccountRequest::find($id);
            
            if($deleteRequest){
                // Send the Delete Request Accept
                Mail::to($deleteRequest->email)->send(new ConfirmationOfDeleteRequestAccount($deleteRequest->name, 'accepted'));
                // delete the user details and update the status of deleteAccountRequest
                $user = User::find($deleteRequest->user_id)->delete();
                $deleteRequest->update(['status'=>1]);
            }
            flash()->success('User has been deleted successfully!');
            return redirect()->route('delete-request.index');
        } catch (Exception $exception) {
            flash()->error($exception->getMessage());
            return redirect()->route('delete-request.index');
        }
    }
    
    
    public function store(Request $request)
    {
        try {
            dd($request->all());
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required',
                'phone' => 'nullable',
                'reason' => 'required',

            ]);

            if ($validator->fails()) {
                $errors = $validator->errors();
                foreach ($errors->all() as $key => $value) {
                    return $this->errorResponse(ucfirst($value), 400);
                }
            }

            $otp = $request->verification;
            $getOTPInDB = DB::table('password_resets')->where('email', $request->email)->first();
            if ($getOTPInDB) {
                if ($otp == $getOTPInDB->otp) {
                    $existingRequest = DeleteAccountRequest::where('email', $request->email)->first();
                    if ($existingRequest) {
                        return response()->json(['message' => 'A delete account request has already been submitted for this email', 'status' => 400]);
                    }
                    $data = new DeleteAccountRequest;
                    $data->name = $request->name;
                    $data->email = $request->email;
                    $data->phone_number = $request->phone;
                    $data->reason_for_deletion = $request->reason;
                    $data->source = 'Web';
                    $userData = User::where('email', $request->email)->first();
                    $data->user_id = $userData->id;
                    $data->status = 0;
                    $data->save();
                    if ($data) {
                        $adminEmail = User::where('role_id', 1)->first();
                        Mail::to($adminEmail->email)->send(new DeleteAccountRequestSendAdmin($data->name, $data->email, $data->reason));

                        return response()->json(['message' => 'Your delete account request has been sent successfully', 'status' => 200]);
                    }
                } else {
                    return response()->json(['message' => 'OTP Does Not Correct , Please Check Again', 'status' => 404]);
                }
            } else {
                return response()->json(['message' => 'Email Does Not Correct, Please Check Again', 'status' => 404]);
            }
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500);
        }
    }
}
