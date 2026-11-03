<?php

namespace App\Mail;

class SummitedRecapMail extends ArasBrandedMail
{
    public function __construct(
        public $recap,
        public $submittedUser,
        public $receiver,
    ) {
        $reviewRoute = (string) $receiver->role_id === '1'
            ? 'admin.show-recap'
            : 'manager.show-recap';

        parent::__construct(
            subjectLine: 'Submitted Recap',
            recipientName: self::resolveFirstName($receiver),
            bodyHtml: view('email.partials.submitted-recap-body', [
                'recap' => $recap,
                'submittedUser' => $submittedUser,
            ])->render(),
            actionText: 'Review Recap',
            actionUrl: route($reviewRoute, $recap->id),
            emailTitle: 'New Recap Submitted',
        );
    }
}
