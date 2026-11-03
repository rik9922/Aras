<?php

namespace App\Mail;

class SendPasswordEmployee extends ArasBrandedMail
{
    public function __construct(
        private string $password,
        private string $name,
    ) {
        parent::__construct(
            subjectLine: 'Your Employee Account Password',
            recipientName: self::resolveFirstName($name),
            bodyHtml: view('email.partials.send-password-employee-body', [
                'password' => $password,
                'name' => $name,
            ])->render(),
            emailTitle: 'Your Employee Account Password',
        );
    }
}
