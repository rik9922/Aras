<?php

namespace App\Mail;

class ClientBudgetAlertMail extends ArasBrandedMail
{
    public function __construct(
        public string $subjectLine,
        public string $headline,
        public array $lines,
        public ?string $actionLabel = null,
        public ?string $actionUrl = null,
    ) {
        parent::__construct(
            subjectLine: $subjectLine,
            recipientName: 'there',
            bodyHtml: view('email.partials.client-budget-alert-body', [
                'headline' => $headline,
                'lines' => $lines,
            ])->render(),
            actionText: $actionLabel,
            actionUrl: $actionUrl,
            emailTitle: $headline,
        );
    }
}
