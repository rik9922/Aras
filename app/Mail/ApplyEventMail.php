<?php

namespace App\Mail;

class ApplyEventMail extends ArasBrandedMail
{
    public function __construct(
        private array $data,
    ) {
        parent::__construct(
            subjectLine: 'Apply Event For Event',
            recipientName: 'there',
            bodyHtml: view('email.partials.apply-event-admin-body', [
                'data' => $data,
            ])->render(),
            emailTitle: 'New Ambassador Application',
        );
    }
}
