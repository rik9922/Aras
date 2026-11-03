<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BEST\EventPushService;
use App\Services\BEST\TokenService;


class PushEventToBestController extends Controller {
    
    
    public function __construct() {
        
    }
    
    public function getAccount(EventPushService $service) {
        $result = $service->getAccount();
        return response()->json($result);
    }
    
    public function getProgram(EventPushService $service) {
        $result = $service->getProgram();
        return response()->json($result);
    }
    
    public function getConsumerEvents(EventPushService $service) {
        $result = $service->getConsumerEvents();
        return response()->json($result);
    }
    
    public function getTags(EventPushService $service) {
        $result = $service->getTags();
        return response()->json($result);
    }
    
    public function getBrandFeatured(EventPushService $service) {
        $result = $service->getBrandFeatured();
        return response()->json($result);
    }
}