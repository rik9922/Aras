<?php

return [
    /*
    |--------------------------------------------------------------------------
    | W-9 / TIN verification
    |--------------------------------------------------------------------------
    |
    | This project currently performs strict local validation for SSN/EIN format
    | to prevent obviously invalid Tax IDs from being submitted.
    |
    | Optionally, you can enable an external verification provider later.
    | Keep this disabled by default to avoid unexpected vendor/API dependencies.
    */

    'verification' => [
        'enabled' => (bool) env('W9_VERIFICATION_ENABLED', false),
        'provider' => env('W9_VERIFICATION_PROVIDER', 'basic'),
    ],
];

