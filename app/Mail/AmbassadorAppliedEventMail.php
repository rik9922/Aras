<?php

namespace App\Mail;

class AmbassadorAppliedEventMail extends ArasBrandedMail
{
    public function __construct(
        private array $data,
    ) {
        parent::__construct(
            subjectLine: 'Your Application for'.$data['event_name'].' Has Been Received',
            recipientName: self::resolveFirstName((string) ($data['name'] ?? '')),
            bodyHtml: view('email.partials.ambassador-applied-event-body', [
                'data' => $data,
            ])->render(),
            emailTitle: 'Application Received',
        );
    }
}
