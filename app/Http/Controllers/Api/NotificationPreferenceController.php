<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use App\Trait\FormatResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationPreferenceController extends Controller
{
    use FormatResponseTrait;

    public function show()
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        if (! Schema::hasTable('notification_preferences')) {
            return $this->successResponse('Notification preferences', [
                'push_enabled' => false,
                'email_enabled' => false,
            ]);
        }

        $preference = NotificationPreference::firstOrCreate(
            ['user_id' => $user->id],
            ['push_enabled' => true, 'email_enabled' => true]
        );

        return $this->successResponse('Notification preferences', [
            'push_enabled' => (bool) $preference->push_enabled,
            'email_enabled' => (bool) $preference->email_enabled,
        ]);
    }

    public function update(Request $request)
    {
        $user = JWTAuth::user();
        if (! $user) {
            return $this->errorResponse('User Not Found', [], 403);
        }

        if (! Schema::hasTable('notification_preferences')) {
            return $this->errorResponse(
                'Notification preferences are not available.',
                'ERROR',
                500
            );
        }

        $validated = $request->validate([
            'push_enabled' => ['sometimes', 'boolean'],
            'email_enabled' => ['sometimes', 'boolean'],
        ]);

        if ($validated === []) {
            return $this->errorResponse('No preference fields provided.', 'validation_error', 422);
        }

        $preference = NotificationPreference::firstOrCreate(
            ['user_id' => $user->id],
            ['push_enabled' => true, 'email_enabled' => true]
        );

        $preference->fill($validated);
        $preference->save();

        return $this->successResponse('Notification preferences updated.', [
            'push_enabled' => (bool) $preference->push_enabled,
            'email_enabled' => (bool) $preference->email_enabled,
        ]);
    }
}
