<?php

namespace App\Mail;

class ConfirmationOfDeleteRequestAccount extends ArasBrandedMail
{
    public function __construct(
        private string $name,
        private string $type,
    ) {
        parent::__construct(
            subjectLine: 'Confirmation of Delete Account Request',
            recipientName: self::resolveFirstName($name),
            bodyHtml: view('email.partials.confirmation-delete-account-body', [
                'type' => $type,
            ])->render(),
            emailTitle: 'Confirmation of Delete Account Request',
        );
    }
}
