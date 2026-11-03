<?php

namespace App\Mail;

class DeleteAccountRequestEmail extends ArasBrandedMail
{
    public function __construct(
        public string $name,
        public string $email,
        public string $reason,
    ) {
        parent::__construct(
            subjectLine: 'Account Deletion Request',
            recipientName: 'Admin',
            bodyHtml: view('email.partials.delete-account-request-body', [
                'name' => $name,
                'email' => $email,
                'reason' => $reason,
            ])->render(),
            emailTitle: 'Account Deletion Request',
        );
    }
}
