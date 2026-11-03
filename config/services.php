<?php

return [

    'tesseract' => [
        'path' => env('TESSERACT_PATH', 'tesseract'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    
    'dwolla' => [
        'environment' => env('DWOLLA_ENVIRONMENT', 'sandbox'),
        'id' => env('DWOLLA_CLIENT_ID'),
        'secret' => env('DWOLLA_CLIENT_SECRET'),
        'webhook_secret' => env('DWOLLA_WEBHOOK_SECRET'),
        'platform_funding_source' => env('DWOLLA_PLATFORM_FUNDING_SOURCE'),
        'same_day_payout_fee' => env('DWOLLA_SAME_DAY_PAYOUT_FEE'),
    ],
    
    'best' => [
        'client_id' => env('BEST_API_CLIENT_ID'),
        'client_secret' => env('BEST_API_CLIENT_SECRET'),
        'token_url' => env('BEST_API_TOKEN_URL'),
        'base_url' => env('BEST_API_BASE_URL'),
        'verify_ssl' => env('BEST_API_VERIFY_SSL', true),
    ],

    'jotform' => [
        'w9_form_url' => env(
            'INDEPENDENT_CONTRACTOR_W9_FORM_URL',
            'https://aras.betaplanets.com/W-9-IC-Agreement_2026.pdf'
        ),
        'ic_agreement_url' => env('INDEPENDENT_CONTRACTOR_AGREEMENT_URL', 'https://aras.betaplanets.com/IC-Agreement_2026.pdf'),
    ],

    'app_download' => [
        'url' => env('AMBASSADOR_APP_DOWNLOAD_URL', ''),
    ],

];
