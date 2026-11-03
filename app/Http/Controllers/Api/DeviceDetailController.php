<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceDetail;
use App\Models\NotificationPreference;
use App\Trait\FormatResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tymon\JWTAuth\Facades\JWTAuth;

class DeviceDetailController extends Controller
{
    use FormatResponseTrait;

    public function saveDeviceDetails(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        if (! Schema::hasTable('device_details')) {
            return $this->errorResponse('Device registration is not available.', 'ERROR', 500);
        }

        $validated = $request->validate([
            'device_token' => 'nullable|string|max:255',
            'platform' => 'nullable|string|max:25',
            'app_version' => 'nullable|string|max:20',
            'is_user_loggedin' => 'nullable|boolean',
            'push_opt_in' => 'nullable|boolean',
            'time_zone' => 'nullable|string|max:255',
        ]);

        $isLoggedIn = array_key_exists('is_user_loggedin', $validated)
            ? (bool) $validated['is_user_loggedin']
            : true;

        $platform = $validated['platform'] ?? ($request->header('X-Device-Platform') ?? 'ios');

        if (! $isLoggedIn) {
            DeviceDetail::clearForUser((int) $user->id);

            return $this->successResponse('Device disconnected.', ['logged_in' => false]);
        }

        if (empty($validated['device_token'])) {
            return $this->successResponse('No device token provided.', ['token_stored' => false]);
        }

        DeviceDetail::upsertForUser((int) $user->id, [
            'device_token' => $validated['device_token'],
            'platform' => $platform,
            'app_version' => $validated['app_version'] ?? null,
            'is_user_loggedin' => 1,
            'time_zone' => $validated['time_zone'] ?? null,
        ]);

        if (Schema::hasTable('notification_preferences')) {
            $preference = NotificationPreference::firstOrCreate(
                ['user_id' => $user->id],
                ['push_enabled' => true, 'email_enabled' => true]
            );

            if (array_key_exists('push_opt_in', $validated) && $validated['push_opt_in'] !== null) {
                $preference->push_enabled = (bool) $validated['push_opt_in'];
                $preference->save();
            }
        }

        return $this->successResponse('Device registered.', [
            'token_stored' => true,
            'logged_in' => true,
            'push_enabled' => Schema::hasTable('notification_preferences')
                ? (bool) (NotificationPreference::where('user_id', $user->id)->value('push_enabled') ?? false)
                : false,
        ]);
    }
}
