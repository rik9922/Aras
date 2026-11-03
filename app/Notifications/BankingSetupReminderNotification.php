<?php

namespace App\Notifications;

use App\Mail\ArasBrandedMail;
use App\Notifications\Concerns\RespectsEmailNotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BankingSetupReminderNotification extends Notification
{
    use Queueable;
    use RespectsEmailNotificationPreference;

    public function __construct(
        public string $setupUrl,
        public string $portalUrl,
    ) {}

    public function toMail(object $notifiable): ArasBrandedMail
    {
        return (new ArasBrandedMail(
            subjectLine: 'Payment Setup Required',
            recipientName: $this->recipientFirstName($notifiable),
            bodyHtml: view('email.partials.banking-setup-reminder-body', [
                'portalUrl' => $this->portalUrl,
            ])->render(),
            actionText: 'Complete Bank Account Setup',
            actionUrl: $this->setupUrl,
            emailTitle: 'Payment Setup Required',
        ))->addressTo($notifiable);
    }
}
