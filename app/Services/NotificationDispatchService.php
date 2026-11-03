<?php

namespace App\Services;

use App\Mail\NotificationMail;
use App\Models\DeviceDetail;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class NotificationDispatchService
{
    private ?array $lastPushResult = null;

    /**
     * PM-required alerts that must always email the manager when an address exists.
     */
    private const ALWAYS_EMAIL_TYPES = [
        'event_check_in',
        'event_late_check_in',
    ];

    /**
     * High-priority alerts that should attempt push even if push_enabled is off.
     */
    private const ALWAYS_PUSH_TYPES = [
        'event_check_in',
        'event_late_check_in',
        'event_check_in_ready',
    ];

    public function countActiveDeviceTokens(int $receiverId): int
    {
        if (! Schema::hasTable('device_details')) {
            return 0;
        }

        return (int) DeviceDetail::query()
            ->where('user_id', $receiverId)
            ->whereNotNull('device_token')
            ->where(function ($q) {
                $q->whereIn('platform', ['web', 'ios', 'android'])
                    ->orWhereNull('platform');
            })
            ->where(function ($q) {
                $q->where('is_user_loggedin', 1)
                    ->orWhereNull('is_user_loggedin');
            })
            ->count();
    }

    public function lastPushResult(): ?array
    {
        return $this->lastPushResult;
    }

    public function dispatch(
        ?int $senderId,
        int $receiverId,
        string $type,
        string $title,
        string $message,
        ?string $emailContentHtml = null,
        ?int $bookingId = null
    ): ?Notification {
        $receiver = User::find($receiverId);
        if (! $receiver) {
            Log::warning('Notification dispatch skipped: receiver not found', [
                'receiver_id' => $receiverId,
                'type' => $type,
            ]);

            return null;
        }

        $visibleTitle = $this->notificationText($title, 100, false);
        $visibleMessage = $this->notificationText($message, 255);
        $forceEmail = in_array($type, self::ALWAYS_EMAIL_TYPES, true);
        $forcePush = in_array($type, self::ALWAYS_PUSH_TYPES, true);

        $pushEnabled = $forcePush;
        $emailEnabled = $forceEmail;
        if (Schema::hasTable('notification_preferences')) {
            // Only create defaults if the preference row doesn't exist.
            // Never overwrite user choices (push_enabled/email_enabled).
            $preference = NotificationPreference::firstOrCreate(
                ['user_id' => $receiverId],
                ['push_enabled' => true, 'email_enabled' => true]
            );

            $pushEnabled = $forcePush || (bool) $preference->push_enabled;
            $emailEnabled = $forceEmail || (bool) $preference->email_enabled;
        }

        $notification = null;
        if (Schema::hasTable('notifications')) {
            $notification = Notification::create([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'title' => $visibleTitle,
                'message' => $visibleMessage,
                'type' => $type,
                'booking_id' => $bookingId,
                'is_seen' => 0,
            ]);
        }

        if ($pushEnabled) {
            $notificationId = $notification?->id;
            if ($notificationId) {
                $this->sendOneSignalPush($receiverId, $notificationId, $type, $visibleTitle, $visibleMessage, $bookingId);
            } else {
                // Still attempt delivery, but without an internal notification id.
                $this->sendOneSignalPush($receiverId, 0, $type, $visibleTitle, $visibleMessage, $bookingId);
            }
        } elseif ($forcePush) {
            Log::warning('Push notification skipped for required alert type', [
                'receiver_id' => $receiverId,
                'type' => $type,
            ]);
        }

        $this->sendEmailNotification(
            $receiver,
            $title,
            $emailContentHtml,
            $emailEnabled,
            $forceEmail,
            $type
        );

        return $notification;
    }

    public function dispatchPushOnly(
        ?int $senderId,
        int $receiverId,
        string $type,
        string $title,
        string $message,
        ?int $bookingId = null,
        bool $force = true
    ): ?Notification {
        $receiver = User::find($receiverId);
        if (! $receiver) {
            Log::warning('Push notification dispatch skipped: receiver not found', [
                'receiver_id' => $receiverId,
                'type' => $type,
            ]);

            return null;
        }

        $visibleTitle = $this->notificationText($title, 100, false);
        $visibleMessage = $this->notificationText($message, 255);
        $pushEnabled = $force;
        if (! $force && Schema::hasTable('notification_preferences')) {
            $preference = NotificationPreference::firstOrCreate(
                ['user_id' => $receiverId],
                ['push_enabled' => true, 'email_enabled' => true]
            );

            $pushEnabled = (bool) $preference->push_enabled;
        }

        $notification = null;
        if (Schema::hasTable('notifications')) {
            $notification = Notification::create([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'title' => $visibleTitle,
                'message' => $visibleMessage,
                'type' => $type,
                'booking_id' => $bookingId,
                'is_seen' => 0,
            ]);
        }

        if ($pushEnabled) {
            $this->sendOneSignalPush($receiverId, $notification?->id ?? 0, $type, $visibleTitle, $visibleMessage, $bookingId);
        } else {
            Log::info('Push notification skipped: preference disabled', [
                'receiver_id' => $receiverId,
                'type' => $type,
            ]);
        }

        return $notification;
    }

    private function sendEmailNotification(
        User $receiver,
        string $title,
        ?string $emailContentHtml,
        bool $emailEnabled,
        bool $forceEmail,
        string $type
    ): void {
        if (! $emailEnabled) {
            Log::info('Notification email skipped: preference disabled', [
                'receiver_id' => (int) $receiver->id,
                'type' => $type,
                'force_email' => $forceEmail,
            ]);

            return;
        }

        if ($emailContentHtml === null || trim($emailContentHtml) === '') {
            Log::warning('Notification email skipped: empty content', [
                'receiver_id' => (int) $receiver->id,
                'type' => $type,
            ]);

            return;
        }

        if (empty($receiver->email)) {
            Log::warning('Notification email skipped: receiver has no email', [
                'receiver_id' => (int) $receiver->id,
                'type' => $type,
            ]);

            return;
        }

        try {
            Mail::to($receiver->email)->send(new NotificationMail([
                'name' => $receiver->name,
                'content' => $emailContentHtml,
            ], $title));

            Log::info('Notification email sent', [
                'receiver_id' => (int) $receiver->id,
                'type' => $type,
                'email' => $receiver->email,
                'forced' => $forceEmail,
            ]);
        } catch (\Throwable $e) {
            Log::error('Notification email failed', [
                'receiver_id' => (int) $receiver->id,
                'type' => $type,
                'email' => $receiver->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendOneSignalPush(
        int $receiverId,
        int $notificationId,
        string $type,
        string $title,
        string $message,
        ?int $bookingId
    ): void {
        $this->lastPushResult = null;

        if (! Schema::hasTable('device_details')) {
            return;
        }

        $devices = DeviceDetail::query()
            ->where('user_id', $receiverId)
            ->whereNotNull('device_token')
            ->where(function ($q) {
                $q->whereIn('platform', ['web', 'ios', 'android'])
                    ->orWhereNull('platform');
            })
            ->where(function ($q) {
                $q->where('is_user_loggedin', 1)
                    ->orWhereNull('is_user_loggedin');
            })
            ->get(['device_token', 'platform']);

        $tokens = $devices
            ->pluck('device_token')
            ->filter()
            ->values()
            ->toArray();

        if (empty($tokens)) {
            Log::warning('OneSignal push skipped: no active device tokens for user', [
                'receiver_id' => $receiverId,
                'type' => $type,
            ]);

            return;
        }

        try {
            $appId = (string) config('app.app_id');
            $authKey = (string) config('app.authorization_key');
            if ($appId === '' || $authKey === '') {
                Log::warning('OneSignal push skipped: missing app_id or authorization_key', [
                    'receiver_id' => $receiverId,
                ]);
                return;
            }

            $httpClient = new Client();
            $platforms = $devices
                ->pluck('platform')
                ->filter()
                ->map(fn ($platform) => strtolower((string) $platform))
                ->unique()
                ->values();
            $launchUrl = $platforms->contains('web') && ! $platforms->contains('ios') && ! $platforms->contains('android')
                ? rtrim((string) config('app.url'), '/') . '/notifications'
                : null;
            $payload = array_filter([
                'app_id' => $appId,
                'include_subscription_ids' => array_values(array_unique(array_map('strval', $tokens))),
                'target_channel' => 'push',
                'isIos' => $platforms->contains('ios') ?: null,
                'isAndroid' => $platforms->contains('android') ?: null,
                'isAnyWeb' => $platforms->contains('web') ?: null,
                'ios_badgeType' => $platforms->contains('ios') ? 'Increase' : null,
                'ios_badgeCount' => $platforms->contains('ios') ? 1 : null,
                'headings' => ['en' => $title],
                'contents' => ['en' => $message],
                'app_url' => $launchUrl,
                'data' => array_filter([
                    'notification_id' => $notificationId ?: null,
                    'type' => $type,
                    'booking_id' => $bookingId ?: null,
                ]),
                'android_channel_id' => config('app.android_channel_id') ?: null,
            ], static fn ($v) => $v !== null);

            $subResponse = null;
            $lastError = null;
            foreach (['Basic', 'Key'] as $authScheme) {
                try {
                    $subResponse = $httpClient->post('https://api.onesignal.com/notifications', [
                        'headers' => [
                            'Authorization' => $authScheme . ' ' . $authKey,
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                        ],
                        'json' => $payload,
                    ]);
                    break;
                } catch (\Throwable $e) {
                    $lastError = $e;
                }
            }

            if (! $subResponse) {
                throw $lastError ?? new \RuntimeException('OneSignal subscription push failed.');
            }

            Log::info('OneSignal push dispatched (subscription ids)', [
                'receiver_id' => $receiverId,
                'count' => count($tokens),
                'status' => $subResponse->getStatusCode(),
                'result' => json_decode((string) $subResponse->getBody(), true),
            ]);
            $this->lastPushResult = [
                'ok' => true,
                'status' => $subResponse->getStatusCode(),
                'response' => json_decode((string) $subResponse->getBody(), true),
            ];
        } catch (\Throwable $e) {
            $responseBody = null;
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $responseBody = json_decode((string) $e->getResponse()->getBody(), true)
                    ?: (string) $e->getResponse()->getBody();
            }

            Log::warning('OneSignal push failed', [
                'receiver_id' => $receiverId,
                'error' => $e->getMessage(),
                'response' => $responseBody,
            ]);
            $this->lastPushResult = [
                'ok' => false,
                'error' => $e->getMessage(),
                'response' => $responseBody,
            ];
        }
    }

    private function notificationText(string $text, int $limit, bool $ensureSentence = true): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/<\s*br\s*\/?>/i', '. ', $text) ?? $text;
        $text = preg_replace('/<\s*\/p\s*>/i', '. ', $text) ?? $text;
        $text = preg_replace('/<a\b[^>]*>.*?<\/a>/is', '', $text) ?? $text;
        $text = strip_tags($text);
        $text = preg_replace('/\[([^\]]*)\]\((?:https?:\/\/|www\.)[^)]*\)/i', '$1', $text) ?? $text;
        $text = preg_replace('/\b(?:open|view|review)\b[^.?!]*(?:here|link)[^.?!]*[.?!]?/i', '', $text) ?? $text;
        $text = preg_replace('#https?://\S+|www\.\S+#i', '', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        $text = preg_replace('/\s+([.,;:!?])/', '$1', $text) ?? $text;
        $text = preg_replace('/([.!?]){2,}/', '$1', $text) ?? $text;
        $text = trim($text, " \t\n\r\0\x0B-:;,.");
        if ($text === '') {
            return '';
        }

        if ($ensureSentence && ! preg_match('/[.!?]$/', $text)) {
            $text .= '.';
        }

        return mb_substr($text, 0, $limit);
    }
}
