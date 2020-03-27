<?php

return [
    'settings' => [
        'displayErrorDetails' => $_ENV['APP_ENV'] == 'development', // set to false in production

        // application metadata
        'application' => [
            'name' => 'Default Application Name',
            'description' => 'This application handles the client side calls amd responses...'
        ],

        'log' => [
            'handler' => null
        ],
    ],
];
