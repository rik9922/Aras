<?php

namespace App\Http\Controllers;

use App\Models\DeviceDetail;
use App\Models\NotificationPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class NotificationSettingsController extends Controller
{
    public function edit()
    {
        $userId = Auth::id();

        if (! Schema::hasTable('notification_preferences')) {
            return view('notifications.settings', [
                'preference' => (object) [
                    'push_enabled' => false,
                    'email_enabled' => false,
                ],
            ]);
        }

        $preference = NotificationPreference::firstOrCreate(
            ['user_id' => $userId],
            ['push_enabled' => true, 'email_enabled' => true]
        );

        return view('notifications.settings', [
            'preference' => $preference,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        if (! Schema::hasTable('notification_preferences')) {
            return redirect()
                ->route('notifications.settings')
                ->with('success', 'Notification preferences will be available after running migrations.');
        }

        $validated = $request->validate([
            'push_enabled' => ['required', 'boolean'],
            'email_enabled' => ['required', 'boolean'],
        ]);

        NotificationPreference::updateOrCreate(
            ['user_id' => $userId],
            $validated
        );

        return redirect()
            ->route('notifications.settings')
            ->with('success', 'Notification preferences updated.');
    }

    public function storePushToken(Request $request): \Illuminate\Http\JsonResponse
    {
        $userId = Auth::id();

        if (! Schema::hasTable('device_details')) {
            return response()->json(['ok' => false, 'message' => 'device_details table missing'], 500);
        }

        $validated = $request->validate([
            'token' => ['nullable', 'string', 'max:255'],
            'opted_in' => ['nullable', 'boolean'],
            'time_zone' => ['nullable', 'string', 'max:255'],
        ]);

        Log::info('Push token sync requested', [
            'user_id' => $userId,
            'has_token' => ! empty($validated['token'] ?? null),
            'opted_in' => $validated['opted_in'] ?? null,
            'time_zone' => $validated['time_zone'] ?? null,
        ]);

        if (Schema::hasTable('notification_preferences') && array_key_exists('opted_in', $validated) && $validated['opted_in'] !== null) {
            $preference = NotificationPreference::firstOrCreate(
                ['user_id' => $userId],
                ['push_enabled' => true, 'email_enabled' => true]
            );
            $preference->push_enabled = (bool) $validated['opted_in'];
            $preference->save();

            // If user opted out, clear all stored push tokens for this user.
            if (! (bool) $validated['opted_in']) {
                DeviceDetail::clearForUser((int) $userId);
                Log::info('Push opt-out: cleared device token', [
                    'user_id' => $userId,
                ]);
            }
        }

        if (empty($validated['token'])) {
            Log::info('Push token sync skipped token storage', [
                'user_id' => $userId,
                'reason' => 'empty token',
            ]);
            return response()->json([
                'ok' => true,
                'token_skipped' => true,
                'token_stored' => false,
            ]);
        }

        DeviceDetail::upsertForUser((int) $userId, [
            'device_token' => $validated['token'],
            'platform' => 'web',
            'app_version' => null,
            'is_user_loggedin' => 1,
            'time_zone' => $validated['time_zone'] ?? null,
        ]);

        Log::info('Push token stored', [
            'user_id' => $userId,
            'token_length' => strlen((string) $validated['token']),
        ]);

        return response()->json([
            'ok' => true,
            'token_stored' => true,
            'token_skipped' => false,
        ]);
    }
}

