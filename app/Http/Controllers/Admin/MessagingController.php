<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NotificationDispatchService;
use App\Models\Market;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessagingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $markets = Market::get();
        return view('admin.messaging.index',compact('markets'));
    }
    
    public function sendMessage(Request $request) {
        $request->validate([
            'market_id' => 'required|array|min:1',
            'market_id.*' => 'integer|exists:markets,id',
            'content' => 'required|array',
        ]);

        $dispatcher = app(NotificationDispatchService::class);
    
        foreach ($request->market_id as $marketId) {
            $messageContent = $request->content[$marketId] ?? null;
    
            if (!$messageContent) {
                continue; // Skip if no content provided for this market
            }
    
            // Get users belonging to this market
            $users = User::select('id', 'name', 'email')
                ->whereRaw('FIND_IN_SET(?, market_id)', [$marketId])
                ->get();
    
            foreach ($users as $user) {
                $plainMessage = trim(preg_replace('/\s+/', ' ', strip_tags((string) $messageContent)));
                $plainMessage = mb_substr($plainMessage, 0, 200);

                $dispatcher->dispatch(
                    Auth::id(),
                    $user->id,
                    'message',
                    'New Message',
                    $plainMessage,
                    (string) $messageContent
                );
            }
        }
    
        return redirect()->route('admin.messaging')->with('success', 'Notifications sent successfully!');
    }


    // public function sendMessage(Request $request) {
    //     $request->validate([
    //         'market_id' => 'required|array|min:1',
    //         'market_id.*' => 'integer|exists:markets,id',
    //         'content' => 'required',
    //     ]);

    //     $users = User::select('id','name','email')->where(function ($query) use ($request) {
    //             foreach ($request->market_id as $marketId) {
    //                 $query->orWhereRaw('FIND_IN_SET(?, market_id)', [$marketId]);
    //             }
    //         })->get();

    //     $content =  $request->content;
    //     foreach($users as $user) {
    //         $data = ['name' => $user->name,'content'=>$content];
    //         Mail::to($user->email)->send(new NotificationMail($data));
    //     }

    //     return redirect()->route('admin.messaging')->with('success', 'Notification Send Successufully!');
    // }
}
