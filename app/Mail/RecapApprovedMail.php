<?php

namespace App\Mail;

class RecapApprovedMail extends ArasBrandedMail
{
    public function __construct(
        public $recap,
    ) {
        parent::__construct(
            subjectLine: 'Recap Approved Mail',
            recipientName: self::resolveFirstName($recap->ambassador),
            bodyHtml: view('email.partials.recap-approved-body', [
                'recap' => $recap,
            ])->render(),
            actionText: 'View Recap',
            actionUrl: url('ambassador/view-recap/'.$recap->id),
            emailTitle: 'Recap Approved',
        );
    }
}
