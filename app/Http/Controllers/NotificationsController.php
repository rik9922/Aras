<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class NotificationsController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $userId = Auth::id();

        if (! Schema::hasTable('notifications')) {
            return view('notifications.index', [
                'notifications' => collect(),
                'unreadCount' => 0,
            ]);
        }

        $notifications = Notification::query()
            ->where('receiver_id', $userId)
            ->orderByDesc('created_at')
            ->paginate(15);

        $unreadCount = Notification::query()
            ->where('receiver_id', $userId)
            ->where('is_seen', 0)
            ->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markSeen(Notification $notification): RedirectResponse
    {
        if (! Schema::hasTable('notifications')) {
            return redirect()->route('notifications.index');
        }

        $userId = Auth::id();
        if ((int) $notification->receiver_id !== (int) $userId) {
            abort(403);
        }

        $notification->is_seen = 1;
        $notification->save();

        return redirect()->route('notifications.index');
    }
}

