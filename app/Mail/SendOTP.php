<?php

namespace App\Mail;

class SendOTP extends ArasBrandedMail
{
    public function __construct(
        private string $token,
        private string $email,
    ) {
        parent::__construct(
            subjectLine: 'One Time Password',
            recipientName: 'there',
            bodyHtml: view('email.partials.password-reset-body', [
                'token' => $token,
                'email' => $email,
            ])->render(),
            emailTitle: 'One Time Password',
        );
    }
}
