<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceDetail;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\NotificationDispatchService;
use App\Trait\FormatResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class TestPushNotificationController extends Controller
{
    use FormatResponseTrait;

    public function __construct(
        private readonly NotificationDispatchService $notificationDispatch
    ) {}

    /**
     * Public test endpoint — POST /api/test-push
     * Body/query: email (required), message (optional)
     * Optional header X-Test-Push-Key when TEST_PUSH_API_KEY is set in .env
     */
    public function send(Request $request)
    {
        $configuredKey = (string) config('app.test_push_api_key');
        if ($configuredKey !== '') {
            $providedKey = (string) $request->header('X-Test-Push-Key', '');
            if ($providedKey === '' || ! hash_equals($configuredKey, $providedKey)) {
                return $this->errorResponse(
                    'Invalid or missing X-Test-Push-Key header.',
                    'unauthorized',
                    Response::HTTP_UNAUTHORIZED,
                    new \stdClass()
                );
            }
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $user = User::where('email', $validated['email'])->first();
        if (! $user) {
            return $this->errorResponse(
                'No user found for this email.',
                'not_found',
                Response::HTTP_NOT_FOUND,
                new \stdClass()
            );
        }

        $message = trim((string) ($validated['message'] ?? ''));
        if ($message === '') {
            $message = 'This is a test push notification from Aras Promotions.';
        }

        $tokenCount = $this->notificationDispatch->countActiveDeviceTokens((int) $user->id);
        $pushEnabled = null;
        if (Schema::hasTable('notification_preferences')) {
            $pushEnabled = (bool) (NotificationPreference::where('user_id', $user->id)->value('push_enabled') ?? true);
        }

        $devices = [];
        if (Schema::hasTable('device_details')) {
            $devices = DeviceDetail::query()
                ->where('user_id', $user->id)
                ->orderByDesc('updated_at')
                ->get(['id', 'platform', 'device_token', 'is_user_loggedin', 'updated_at'])
                ->map(fn ($row) => [
                    'id' => $row->id,
                    'platform' => $row->platform,
                    'token_preview' => $row->device_token
                        ? substr((string) $row->device_token, 0, 8).'…'
                        : null,
                    'token_length' => $row->device_token ? strlen((string) $row->device_token) : 0,
                    'token_shape' => $this->describeTokenShape((string) ($row->device_token ?? '')),
                    'is_user_loggedin' => $row->is_user_loggedin,
                    'updated_at' => $row->updated_at?->toIso8601String(),
                ])
                ->values()
                ->all();
        }

        $notification = $this->notificationDispatch->dispatchPushOnly(
            null,
            (int) $user->id,
            'test_push',
            'Test Notification',
            $message,
            null,
            false
        );

        $diagnostics = [
            'user_id' => $user->id,
            'email' => $user->email,
            'role_id' => (int) $user->role_id,
            'role_name' => $user->role?->name,
            'push_enabled' => $pushEnabled,
            'active_device_tokens' => $tokenCount,
            'device_rows' => $devices,
            'notification_id' => $notification?->id,
            'push_attempted' => $tokenCount > 0 && $pushEnabled !== false,
            'onesignal' => $this->notificationDispatch->lastPushResult(),
            'hint' => $tokenCount === 0
                ? 'No device token in database. User must open the mobile app, allow notifications, and log in.'
                : null,
        ];

        if ($pushEnabled === false) {
            return $this->successResponse(
                'Test notification saved. Push is disabled in notification preferences.',
                $diagnostics
            );
        }

        if ($tokenCount === 0) {
            return $this->errorResponse(
                'User has no registered device tokens. Push was not sent.',
                'no_device_tokens',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $diagnostics
            );
        }

        return $this->successResponse('Test push notification dispatched.', $diagnostics);
    }

    private function describeTokenShape(string $token): string
    {
        if ($token === '') {
            return 'empty';
        }

        if (preg_match('/^[0-9a-f]{64}$/i', $token)) {
            return 'apns_token';
        }

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $token)) {
            return 'uuid';
        }

        return 'unknown';
    }

    /**
     * Authenticated test endpoint — POST /api/auth/v1/test_push
     * Sends to the currently logged-in app user and respects push preferences.
     */
    public function sendToAuthenticatedUser(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $message = trim((string) ($validated['message'] ?? ''));
        if ($message === '') {
            $message = 'This is a test push notification from Aras Promotions.';
        }

        $tokenCount = $this->notificationDispatch->countActiveDeviceTokens((int) $user->id);
        $pushEnabled = true;
        if (Schema::hasTable('notification_preferences')) {
            $pushEnabled = (bool) (NotificationPreference::where('user_id', $user->id)->value('push_enabled') ?? true);
        }

        $notification = $this->notificationDispatch->dispatchPushOnly(
            null,
            (int) $user->id,
            'test_push',
            'Test Notification',
            $message,
            null,
            false
        );

        $diagnostics = [
            'user_id' => $user->id,
            'email' => $user->email,
            'push_enabled' => $pushEnabled,
            'active_device_tokens' => $tokenCount,
            'notification_id' => $notification?->id,
            'push_attempted' => $tokenCount > 0 && $pushEnabled,
            'onesignal' => $this->notificationDispatch->lastPushResult(),
            'hint' => $tokenCount === 0
                ? 'No device token in database. Open the mobile app and allow notifications.'
                : null,
        ];

        if (! $pushEnabled) {
            return $this->successResponse(
                'Test notification saved. Push is disabled in notification preferences.',
                $diagnostics
            );
        }

        if ($tokenCount === 0) {
            return $this->errorResponse(
                'User has no registered device tokens. Push was not sent.',
                'no_device_tokens',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $diagnostics
            );
        }

        return $this->successResponse('Test push notification dispatched.', $diagnostics);
    }
}
