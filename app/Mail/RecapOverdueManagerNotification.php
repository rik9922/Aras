<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\Recap;

class RecapOverdueManagerNotification extends ArasBrandedMail
{
    public function __construct(Event $event, ?Recap $recap = null)
    {
        $event->loadMissing(['manager', 'brand', 'location', 'recap']);

        $reviewUrl = route('manager.show-recap', $recap?->id ?? $event->recap?->id ?? $event->id);

        parent::__construct(
            subjectLine: 'Recap attention required',
            recipientName: self::resolveFirstName($event->manager),
            bodyHtml: view('email.partials.recap-overdue-manager-body', [
                'event' => $event,
                'recap' => $recap,
            ])->render(),
            actionText: 'Review Recap',
            actionUrl: $reviewUrl,
            emailTitle: 'Recap attention required',
        );
    }
}
