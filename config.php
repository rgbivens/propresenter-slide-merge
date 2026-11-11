<?php
/*
 * Slide Merge settings. Fill in the four database values and a stats password.
 * This file is blocked from the web and prints nothing if requested directly.
 */
if (!defined('SM_APP')) { http_response_code(404); exit; }

return [
    'db' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'DATABASE_NAME',
        'user'     => 'DATABASE_USER',
        'password' => 'DATABASE_PASSWORD',
    ],

    // Password for stats.php. Use something long; anyone with it can see the counts.
    'stats_password' => 'CHANGE_ME',

    // Time zone used to group counts by day on the stats page.
    'timezone' => 'America/Chicago',
];
