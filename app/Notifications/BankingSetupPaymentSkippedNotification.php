<?php

namespace App\Notifications;

use App\Mail\ArasBrandedMail;
use App\Notifications\Concerns\RespectsEmailNotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BankingSetupPaymentSkippedNotification extends Notification
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
            subjectLine: 'Action Required: Complete Direct Deposit Setup',
            recipientName: $this->recipientFirstName($notifiable),
            bodyHtml: view('email.partials.banking-setup-payment-skipped-body', [
                'portalUrl' => $this->portalUrl,
            ])->render(),
            actionText: 'Complete Bank Account Setup',
            actionUrl: $this->setupUrl,
            emailTitle: 'Action Required: Complete Direct Deposit Setup',
        ))->addressTo($notifiable);
    }
}
