<?php

return [

    'enabled' => (bool) env('BANKING_SETUP_REMINDERS_ENABLED', true),

    /** Minimum days between automated reminders per ambassador. */
    'interval_days' => (int) env('BANKING_SETUP_REMINDER_INTERVAL_DAYS', 7),

    /** Skip ambassadors created within this many days (welcome email covers initial onboarding). */
    'grace_days_after_creation' => (int) env('BANKING_SETUP_REMINDER_GRACE_DAYS', 3),

];
