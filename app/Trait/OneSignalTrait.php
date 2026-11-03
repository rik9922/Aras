<?php
namespace App\Traits;

use App\Models\User;
use App\Models\DeviceDetail;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use onesignal\client\api\DefaultApi;
use onesignal\client\Configuration;
use onesignal\client\model\Notification as OneSignalNotification;
use onesignal\client\model\StringMap;

trait OneSignalTrait {
    
    function sendNotification($title,$message,$data,$external_id){
        try {
            $heading = new StringMap();
            $heading->setEn($title);

            $content = new StringMap();
            $content->setEn($message);

            $osNotification = new OneSignalNotification();
            $osNotification->setAppId((string) config('app.app_id'));
            $osNotification->setIncludePlayerIds((array) $external_id);
            $osNotification->setContents($content);
            $osNotification->setHeadings($heading);
            $osNotification->setData((object) $data);

            if (! empty(config('app.android_channel_id'))) {
                $osNotification->setAndroidChannelId((string) config('app.android_channel_id'));
            }

            // The OneSignal PHP SDK uses appKeyToken to populate `Authorization: Key <REST_API_KEY>`.
            $config = Configuration::getDefaultConfiguration()
                ->setAppKeyToken((string) config('app.authorization_key'));

            $apiInstance = new DefaultApi(new \GuzzleHttp\Client(), $config);
            $result = $apiInstance->createNotification($osNotification);

            Log::info('OneSignal notification dispatched (trait)', ['result' => $result]);
        } catch (\Throwable $e) {
            Log::warning('OneSignal notification failed (trait)', [
                'error' => $e->getMessage(),
            ]);
        }
   
    }
    
    
    public function storeNotification($senderId,$receiverId,$title,$message,$type,$bookingId){
        
        $noti = new Notification;
        $noti->sender_id = isset($senderId) ? $senderId : null;
        $noti->receiver_id = $receiverId;
        $noti->title = $title;
        $noti->message = $message;
        $noti->type = $type;
        $noti->booking_id = isset($bookingId) ? $bookingId : null;
        $noti->save();
        if($noti){
            return true;
        }
    }
    
    
    
    
    
    public function notifyMessage($sender,$receiverId,$detail=null,$type=null,$title,$msg,$bookingId){
        $data = [];
        $getToken = DeviceDetail::where(['user_id'=> $receiverId,'is_user_loggedin'=>1])->pluck('device_token')->toArray();
        $token = $getToken;
        $senderDetails = [
                        "user_name" => ($sender->first_name ?? '') . ' ' . ($sender->last_name ?? '') ??null,
                        "user_image" => $sender->image ?? null,
                        "user_id" => isset($sender) ? $sender->id : null,
                        ];
                    
        $senderId = isset($sender) ? $sender->id : null;
        $senderName = ($sender->first_name ?? '') . ' ' . ($sender->last_name ?? '') ?? null;
        
        switch ($type){
            
            case "assign_photographer_booking":
                $title = "Booking Assigned";
                $message = "Your booking has been assigned by the $senderName. Please check the details.";
                $data = [
                    "type" => $type,
                    "sender" => $senderDetails,
                    "booking_id" => $detail,
                ];
               
            break;
            
            case "assign_editor_booking":
                $title = "Booking Assigned";
                $message = "Your booking has been assigned by the $senderName. Please check the details.";
                $data = [
                    "type" => $type,
                    "sender" => $senderDetails,
                    "booking_id" => $detail,
                ];
               
            break;

            case "follow": 
                $title = "New Follow";
                $message = "$senderName started following you.";
                $data = [
                    "type" => $type,
                    "sender" => $senderDetails,
                ];

            break;
                
            case "custom_notification": 
                $title = $title;
                $message = $msg;
                $data = [
                    "type" => $title,
                    "sender" => $senderDetails,
                ];
            break;
                
                
            case "accept_booking":
                $title = "Accepted Booking";
                $message = "Your booking request has been accepted";
                $data = [
                    "type" => $title,
                    "sender" => $senderDetails,
                    "booking_id" => $detail,
                    
                ];
            break;
                
            case "reject_booking":
                $title = "Rejected Booking";
                $message = "Your booking request has been rejected";
                $data = [
                    "type" => $title,
                    "sender" => $senderDetails,
                     "booking_id" => $detail,
                ];
            break;
            
            case "image_proof_request":
                $title = "Selected Image Proof";
                $message = "Customer has added an image request for edit";
                $data = [
                    "type" => $title,
                    "sender" => $senderDetails,
                    
                ];
            break;                
            default:
                return 1 ;
        }
        
        if(!empty($token)){
            $this->sendNotification($title,$message,$data,$token);
        }
        $this->storeNotification($senderId,$receiverId,$title,$message,$type,$bookingId);
       
    }
        
        
        
}



