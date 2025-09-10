<?php

return [
    'api' => [
        'title' => 'Coffee Management API',
        'description' => 'API documentation for Coffee Management System',
        'version' => '1.0.0',
    ],

    'ui' => [
        'theme' => 'default',
        'show_sidebar' => true,
    ],

    'routes' => [
        'domain' => null,
        'prefix' => 'api',
        'middleware' => [],
    ],

    'servers' => [
        [
            'url' => 'http://127.0.0.1:8000/api',
            'description' => 'Local Development Server',
        ],
    ],

    'middleware' => [
        \Dedoc\Scramble\Http\Middleware\ScrambleMiddleware::class,
    ],

    'extensions' => [
        // Add custom extensions here if needed
    ],
];
