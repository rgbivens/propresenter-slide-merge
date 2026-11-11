<?php
/*
 * Slide Merge settings. Copy this file to config.php, then fill in the database
 * values and a stats password. config.php is listed in .gitignore so your
 * password stays out of the repository. It prints nothing if requested directly.
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
