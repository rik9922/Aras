<?php

namespace App\Mail;

class RegistrationMail extends ArasBrandedMail
{
    public function __construct(
        private array $data,
    ) {
        parent::__construct(
            subjectLine: 'Registration',
            recipientName: self::resolveFirstName((string) ($data['name'] ?? '')),
            bodyHtml: view('email.partials.registration-body', [
                'data' => $data,
            ])->render(),
            emailTitle: 'Registration',
        );
    }
}
