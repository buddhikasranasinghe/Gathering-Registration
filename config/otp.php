<?php

return [

    /*
    |--------------------------------------------------------------------------
    | One-Time Passcode Policy
    |--------------------------------------------------------------------------
    |
    | These values control the passwordless sign-in codes that are emailed to
    | users. Codes are stored hashed, can only be used once, and expire after
    | the configured number of minutes.
    |
    */

    'code_length' => 6,

    'expires_after_minutes' => 10,

    'max_attempts' => 5,

    'resend_cooldown_seconds' => 60,

];
