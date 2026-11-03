<?php

namespace App\Mail;

class AssignAmbassadorMail extends ArasBrandedMail
{
    /**
     * @param  array{event_name: string, event_date: string, location: string, name: string, app_download_url: string}  $data
     */
    public function __construct(array $data)
    {
        parent::__construct(
            subjectLine: 'Assigned Ambassador',
            recipientName: self::resolveFirstName((string) ($data['name'] ?? '')),
            bodyHtml: view('email.partials.assigned-event-body', $data)->render(),
            emailTitle: 'Assigned Ambassador',
        );
    }
}
