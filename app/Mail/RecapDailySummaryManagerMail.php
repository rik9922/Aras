<?php

namespace App\Mail;

use App\Models\User;

class RecapDailySummaryManagerMail extends ArasBrandedMail
{
    public function __construct(User $manager, int $recapCount)
    {
        $recapLabel = $recapCount === 1 ? 'recap was' : 'recaps were';

        parent::__construct(
            subjectLine: 'Daily Recap Summary',
            recipientName: self::resolveFirstName($manager),
            bodyHtml: view('email.partials.recap-daily-summary-manager-body', [
                'recapCount' => $recapCount,
                'recapLabel' => $recapLabel,
            ])->render(),
            actionText: 'Review Recaps',
            actionUrl: route('manager.recaps'),
            emailTitle: 'Daily Recap Summary',
        );
    }
}
