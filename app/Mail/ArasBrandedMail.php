<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArasBrandedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $recipientName,
        public string $bodyHtml,
        public ?string $actionText = null,
        public ?string $actionUrl = null,
        public ?string $emailTitle = null,
        public string $signature = 'Aras Promotions',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'email.layouts.aras',
            with: [
                'emailTitle' => $this->emailTitle ?? $this->subjectLine,
                'recipientName' => $this->recipientName,
                'bodyHtml' => $this->bodyHtml,
                'actionText' => $this->actionText,
                'actionUrl' => $this->actionUrl,
                'signature' => $this->signature,
                'logoUrl' => asset('assets/images/aras-mail.png'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    public function addressTo(object $notifiable): static
    {
        $email = $notifiable->routeNotificationFor('mail');

        if (is_array($email)) {
            return $this->to($email);
        }

        return $this->to((string) $email, $notifiable->name ?? null);
    }

    public static function resolveFirstName(string|object|null $nameOrUser): string
    {
        if (is_object($nameOrUser)) {
            $firstName = trim((string) ($nameOrUser->first_name ?? ''));
            if ($firstName !== '') {
                return $firstName;
            }

            $name = trim((string) ($nameOrUser->name ?? ''));
        } else {
            $name = trim((string) ($nameOrUser ?? ''));
        }

        if ($name === '' || $name === '-') {
            return 'there';
        }

        return strtok($name, ' ') ?: $name;
    }
}
