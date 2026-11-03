<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Event check-in geofence radius (meters)
    |--------------------------------------------------------------------------
    */
    'check_in_radius_meters' => (int) env('EVENT_CHECK_IN_RADIUS_METERS', 50),

    /*
    |--------------------------------------------------------------------------
    | Late check-in alert grace period (minutes after event start)
    |--------------------------------------------------------------------------
    */
    'late_checkin_grace_minutes' => (int) env('EVENT_LATE_CHECKIN_GRACE_MINUTES', 10),

];
