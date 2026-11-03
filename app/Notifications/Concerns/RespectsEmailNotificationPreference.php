<?php

namespace App\Notifications\Concerns;

use App\Models\NotificationPreference;
use Illuminate\Support\Facades\Schema;

trait RespectsEmailNotificationPreference
{
    use ResolvesRecipientName;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if (! $this->emailNotificationsEnabled($notifiable)) {
            return [];
        }

        return ['mail'];
    }

    protected function emailNotificationsEnabled(object $notifiable): bool
    {
        if (empty($notifiable->email)) {
            return false;
        }

        if (! Schema::hasTable('notification_preferences')) {
            return true;
        }

        $preference = NotificationPreference::firstOrCreate(
            ['user_id' => $notifiable->id],
            ['push_enabled' => true, 'email_enabled' => true]
        );

        return (bool) $preference->email_enabled;
    }
}
