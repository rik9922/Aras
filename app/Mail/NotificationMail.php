<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    private array $data;

    private string $subjectLine;

    /**
     * @param  array{name?: string, content?: string, actionText?: string|null, actionUrl?: string|null, signature?: string|null}  $data
     */
    public function __construct(array $data, ?string $subjectLine = null)
    {
        $this->data = $data;
        $this->subjectLine = $subjectLine ?: 'Notification';
    }

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
                'emailTitle' => $this->subjectLine,
                'recipientName' => ArasBrandedMail::resolveFirstName((string) ($this->data['name'] ?? '')),
                'bodyHtml' => (string) ($this->data['content'] ?? ''),
                'actionText' => $this->data['actionText'] ?? null,
                'actionUrl' => $this->data['actionUrl'] ?? null,
                'signature' => $this->data['signature'] ?? 'Aras Promotions',
                'logoUrl' => asset('assets/images/aras-mail.png'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

}
