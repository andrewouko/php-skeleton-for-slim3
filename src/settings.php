<?php
use Dotenv\Dotenv;


$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

return [
    'settings' => [
        'displayErrorDetails' => $_ENV['APP_ENV'] == 'development', // set to false in production

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // application metadata
        'application' => [
            'name' => 'Interswitch Kenya API',
            'description' => 'This application handles the client side calls amd responses to the Interswitch Kenya Bill Payments API.'
        ],

        'log' => [
            'handler' => null
        ],
    ],
];
