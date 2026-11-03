<?php

return [
    'client_id' => env('QUICKBOOKS_CLIENT_ID'),
    'client_secret' => env('QUICKBOOKS_CLIENT_SECRET'),
    'redirect_uri'         => env('QUICKBOOKS_REDIRECT_URI', rtrim((string) env('APP_URL', ''), '/').'/admin/quickbooks/callback'),
    'redirect_uri_manager' => env('QUICKBOOKS_REDIRECT_URI_MANAGER', rtrim((string) env('APP_URL', ''), '/').'/manager/quickbooks/callback'),
    'access_token' => env('QUICKBOOKS_ACCESS_TOKEN'),
    'refresh_token' => env('QUICKBOOKS_REFRESH_TOKEN'),
    'realm_id' => env('QUICKBOOKS_REALM_ID'),
    'base_url' => env('QUICKBOOKS_BASE_URL', 'development'),
    'income_account_id' => env('QUICKBOOKS_INCOME_ACCOUNT_ID', '79'),
    'expense_account_id' => env('QUICKBOOKS_EXPENSE_ACCOUNT_ID', '80'),
    'expense_account_name' => env('QUICKBOOKS_EXPENSE_ACCOUNT_NAME', 'Ambassador Payouts'),
    'bank_account_id' => env('QUICKBOOKS_BANK_ACCOUNT_ID'),
    'webhook_verifier_token' => env('QUICKBOOKS_WEBHOOK_VERIFIER_TOKEN'),
    'scope' => 'com.intuit.quickbooks.accounting openid profile email phone address',
    'authorizationRequestUrl' => 'https://appcenter.intuit.com/connect/oauth2',
    'tokenEndPointUrl' => 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer',
];
