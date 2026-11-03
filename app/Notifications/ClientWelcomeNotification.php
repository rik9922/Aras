<?php

namespace App\Notifications;

use App\Mail\ArasBrandedMail;
use App\Notifications\Concerns\ResolvesRecipientName;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ClientWelcomeNotification extends Notification
{
    use Queueable;
    use ResolvesRecipientName;

    public function __construct(
        public string $portalUrl,
        public string $username,
        public string $password,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return empty($notifiable->email) ? [] : ['mail'];
    }

    public function toMail(object $notifiable): ArasBrandedMail
    {
        return (new ArasBrandedMail(
            subjectLine: 'Welcome to Aras Promotions',
            recipientName: $this->recipientFirstName($notifiable),
            bodyHtml: view('email.partials.client-welcome-body', [
                'portalUrl' => $this->portalUrl,
                'username' => $this->username,
                'password' => $this->password,
            ])->render(),
            actionText: 'Log In to Portal',
            actionUrl: $this->portalUrl,
            emailTitle: 'Welcome to Aras Promotions',
        ))->addressTo($notifiable);
    }
}
