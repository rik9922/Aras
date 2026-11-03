<?php

namespace App\Notifications;

use App\Mail\ArasBrandedMail;
use App\Notifications\Concerns\ResolvesRecipientName;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class AmbassadorWelcomeNotification extends Notification
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
        $mail = (new ArasBrandedMail(
            subjectLine: 'Welcome to the Aras Promotions Team',
            recipientName: $this->recipientFirstName($notifiable),
            bodyHtml: view('email.partials.ambassador-welcome-body', [
                'portalUrl' => $this->portalUrl,
                'username' => $this->username,
                'password' => $this->password,
                'profileUrl' => route('ambassador.profile'),
                'bankAccountUrl' => route('ambassador.dwolla.bank-account'),
                'w9FormUrl' => config('services.jotform.w9_form_url'),
            ])->render(),
            actionText: 'Log In to Portal',
            actionUrl: $this->portalUrl,
            emailTitle: 'Welcome to the Aras Promotions Team',
        ))->addressTo($notifiable);

        // Attach the documents, rather than only linking to them in the body.
        foreach ([
            'w9FormUrl' => 'W-9-2026.pdf',
            'icAgreementUrl' => 'Independent-Contractor-Agreement-2026.pdf',
        ] as $configKey => $filename) {
            $url = (string) config('services.jotform.'.($configKey === 'w9FormUrl' ? 'w9_form_url' : 'ic_agreement_url'), '');
            if ($url === '') {
                continue;
            }
            try {
                $response = Http::timeout(15)->get($url);
                if ($response->successful()) {
                    $mail->attachData($response->body(), $filename, ['mime' => 'application/pdf']);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $mail;
    }
}
