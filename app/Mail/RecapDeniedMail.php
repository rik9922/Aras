<?php

namespace App\Mail;

class RecapDeniedMail extends ArasBrandedMail
{
    public function __construct(
        public $recap,
    ) {
        parent::__construct(
            subjectLine: 'Recap Denied Mail',
            recipientName: self::resolveFirstName($recap->ambassador),
            bodyHtml: view('email.partials.recap-denied-body', [
                'recap' => $recap,
            ])->render(),
            actionText: 'View Recap',
            actionUrl: url('/ambassador/view-recap/'.$recap->id),
            emailTitle: 'Recap Denied',
        );
    }
}
