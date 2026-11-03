<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Trait\FormatResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationController extends Controller
{
    use FormatResponseTrait;

    public function unreadCount()
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', 'unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        return $this->successResponse('Unread notification count.', [
            'unread_count' => $this->unreadCountForUser((int) $user->id),
        ]);
    }

    public function index(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', 'unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        if (! Schema::hasTable('notifications')) {
            return $this->successResponse('Notifications fetched successfully.', [
                'notifications' => [],
                'unread_count' => 0,
            ]);
        }

        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 50));

        $notifications = Notification::query()
            ->where('receiver_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->successResponse('Notifications fetched successfully.', [
            'notifications' => $notifications->items(),
            'pagination' => [
                'total' => $notifications->total(),
                'total_page' => $notifications->lastPage(),
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'has_more' => $notifications->hasMorePages(),
            ],
            'unread_count' => $this->unreadCountForUser((int) $user->id),
        ]);
    }

    public function markAsRead(int $notification)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', 'unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        $item = $this->findUserNotification((int) $user->id, $notification);
        if (! $item) {
            return $this->errorResponse('Notification not found.', 'not_found', Response::HTTP_NOT_FOUND);
        }

        $item->is_seen = 1;
        $item->save();

        return $this->successResponse('Notification marked as read.', [
            'notification' => $item,
            'unread_count' => $this->unreadCountForUser((int) $user->id),
        ]);
    }

    public function markAllAsRead()
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', 'unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        if (Schema::hasTable('notifications')) {
            Notification::query()
                ->where('receiver_id', $user->id)
                ->where('is_seen', 0)
                ->update(['is_seen' => 1]);
        }

        return $this->successResponse('Notifications marked as read.', [
            'unread_count' => 0,
        ]);
    }

    public function destroy(int $notification)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', 'unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        $item = $this->findUserNotification((int) $user->id, $notification);
        if (! $item) {
            return $this->errorResponse('Notification not found.', 'not_found', Response::HTTP_NOT_FOUND);
        }

        $item->delete();

        return $this->successResponse('Notification deleted.', [
            'unread_count' => $this->unreadCountForUser((int) $user->id),
        ]);
    }

    public function destroyAll()
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', 'unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        if (Schema::hasTable('notifications')) {
            Notification::query()
                ->where('receiver_id', $user->id)
                ->delete();
        }

        return $this->successResponse('Notifications deleted.', [
            'unread_count' => 0,
        ]);
    }

    private function findUserNotification(int $userId, int $notificationId): ?Notification
    {
        if (! Schema::hasTable('notifications')) {
            return null;
        }

        return Notification::query()
            ->where('receiver_id', $userId)
            ->where('id', $notificationId)
            ->first();
    }

    private function unreadCountForUser(int $userId): int
    {
        if (! Schema::hasTable('notifications')) {
            return 0;
        }

        return (int) Notification::query()
            ->where('receiver_id', $userId)
            ->where('is_seen', 0)
            ->count();
    }
}
