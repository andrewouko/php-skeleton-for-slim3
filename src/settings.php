<?php

use Monolog\Handler\StreamHandler;


$current_time = new \DateTime(null, new \DateTimeZone('Africa/Nairobi'));

return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production

        // application metadata
        'application' => [
            'name' => 'Default Application Name',
            'description' => 'This application handles the client side calls amd responses...'
        ],

        'log' => [
            'handler' => new StreamHandler('/var/www/html/personal_repos/php-skeleton-for-slim3/logs' . $current_time->format('Y-m-d').'.log')
        ],
    ],
];
