<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Late threshold (time of day)
    |--------------------------------------------------------------------------
    | Check-in after this time is considered "Late". Use 24h format HH:mm.
    */
    'late_threshold' => env('ATTENDANCE_LATE_THRESHOLD', '09:00'),

    /*
    |--------------------------------------------------------------------------
    | Include inactive/resigned employees in tracking by default
    |--------------------------------------------------------------------------
    */
    'include_inactive_by_default' => false,
];
