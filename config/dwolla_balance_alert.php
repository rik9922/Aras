<?php

return [
    'enabled' => (bool) env('DWOLLA_BALANCE_ALERT_ENABLED', true),
    'forecast_days' => (int) env('DWOLLA_BALANCE_ALERT_FORECAST_DAYS', 14),
    'buffer_amount' => (float) env('DWOLLA_BALANCE_ALERT_BUFFER_AMOUNT', 20000),
    'fixed_floor_amount' => env('DWOLLA_BALANCE_ALERT_FIXED_FLOOR_AMOUNT'),
    'warning_gap' => (float) env('DWOLLA_BALANCE_ALERT_WARNING_GAP', 0),
    'critical_gap' => (float) env('DWOLLA_BALANCE_ALERT_CRITICAL_GAP', 10000),
    'cooldown_hours' => (int) env('DWOLLA_BALANCE_ALERT_COOLDOWN_HOURS', 12),
    // Default roles: Administrator (1) only — Manager (6) temporarily disabled.
    // To re-enable Manager notifications, update the default back to '1,6' or set DWOLLA_BALANCE_ALERT_NOTIFY_ROLE_IDS=1,6 in .env.
    'notify_role_ids' => array_map('intval', array_filter(explode(',', (string) env('DWOLLA_BALANCE_ALERT_NOTIFY_ROLE_IDS', '1')))),
    // Optional explicit users (comma-separated user IDs)
    'notify_user_ids' => array_map('intval', array_filter(explode(',', (string) env('DWOLLA_BALANCE_ALERT_NOTIFY_USER_IDS', '')))),
];
