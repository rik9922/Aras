<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Management notification inboxes (budget alerts & approval requests)
    |--------------------------------------------------------------------------
    |
    | Comma-separated list in .env: BUDGET_MANAGEMENT_EMAILS="ops@example.com,finance@example.com"
    |
    */
    'management_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('BUDGET_MANAGEMENT_EMAILS', ''))
    ))),

];
