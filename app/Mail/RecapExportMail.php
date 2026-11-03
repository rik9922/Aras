<?php

namespace App\Mail;

use App\Models\Recap;
use Illuminate\Mail\Mailables\Attachment;

class RecapExportMail extends ArasBrandedMail
{
    public function __construct(
        public Recap $recap,
        public string $excelContent,
        public string $filename,
    ) {
        $this->recap->loadMissing(['event.brand']);

        $event = $this->recap->event;
        $eventName = $event?->event_name ?? 'Recap';

        parent::__construct(
            subjectLine: 'Recap Export - '.$eventName,
            recipientName: 'there',
            bodyHtml: view('email.partials.recap-export-body', [
                'recap' => $this->recap,
                'event' => $event,
            ])->render(),
            emailTitle: 'Recap Export',
        );
    }

    public function attachments(): array
    {
        $content = $this->excelContent;
        $filename = $this->filename;

        return [
            Attachment::fromData(fn () => $content, $filename)
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}
