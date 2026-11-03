<?php

namespace App\Http\Controllers\Api;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Requests\VerifyOTP;
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
use App\Services\EventService;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CommonController extends Controller
{
    protected $eventService;
    use FormatResponseTrait;
    
    public function __construct(EventService $eventService){
        $this->eventService = $eventService;
    }
    
    public function getUsers(Request $request) {
        $user = JWTAuth::user();
        if($user){
            $users=[];
            if($user->role_id==5){
                $users=User::whereIn('role_id',[1,6])->get();
                
            } else if($user->role_id==6){
                $users=User::whereIn('role_id',[1,5])->get();
                
            } if($user->role_id==1){
                $users=User::whereIn('role_id',[5,6])->get();
            }
            return $this->successResponse('User Data.', $users);
        }
        return $this->errorResponse('Event Not Found', [], 403);
    }
    
    public function chatFileUpload(Request $request){
        $user = JWTAuth::user();
        if (!$user) {
            return $this->errorResponse('Unauthorized', [], 401);
        }
        if ($request->hasFile('picture')) {
            $app_url    =   config('app.url');
            // $path       =   storage_path().'/app/public/chat/'.$user->id;
            $path       =   public_path('chat/');
            $file       =   $request->file('picture');
            $name       =   Carbon::now()->timestamp.'.'.$file->getClientOriginalExtension();
            $file->move($path, $name);  
            // $image = ($app_url.'storage/app/public/chat/'.$user->id.'/'.$name);
            $image = ($app_url.'/public/chat/'.$name);
        }
        return $this->successResponse('File uploaded successfully.', $image);
    }
}
    