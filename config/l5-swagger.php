<?php

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'Coffee Management API',
                'description' => 'API documentation for Coffee Management System',
                'version' => '1.0.0',
            ],
            'swagger_version' => env('SWAGGER_VERSION', '2.0'),
            'exclude' => [
                '/health/public',
                '/health'
            ],
            'security' => [
                ['Bearer' => []],
            ],
            'securityDefinitions' => [
                'securitySchemes' => [
                    'Bearer' => [
                        'type' => 'apiKey',
                        'description' => 'Enter token in format: Bearer {token}',
                        'name' => 'Authorization',
                        'in' => 'header',
                    ],
                ],
            ],
            'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),
            'generate_yaml_copy' => env('L5_SWAGGER_GENERATE_YAML_COPY', false),
            'proxy' => false,
            'additional_config_url' => null,
            'operations_sort' => env('L5_SWAGGER_OPERATIONS_SORT', null),
            'validator_url' => null,
            'ui' => [
                'display' => [
                    'doc_expansion' => env('L5_SWAGGER_UI_DOC_EXPANSION', 'none'),
                    'filter' => env('L5_SWAGGER_UI_FILTERS', true),
                ],
                'authorization' => [
                    'persist_authorization' => env('L5_SWAGGER_UI_PERSIST_AUTHORIZATION', true),
                    'oauth2_redirect_url' => env('L5_SWAGGER_UI_OAUTH2_REDIRECT_URL', null),
                ],
            ],
            'paths' => [
                'docs' => storage_path('api-docs'),
                'views' => base_path('resources/views/vendor/l5-swagger'),
                'base' => env('L5_SWAGGER_BASE_PATH', null),
                'group' => env('L5_SWAGGER_GROUP_PATH', null),
                'excludes' => [],
            ],
        ],
    ],
    'defaults' => [
        'routes' => [
            'api' => 'api/documentation',
            'docs' => 'docs',
            'assets' => 'docs/asset/',
            'oauth2_callback' => 'api/oauth2-callback',
            'middleware' => [
                'api' => [],
                'asset' => [],
                'docs' => [],
                'oauth2_callback' => [],
            ],
        ],
        'paths' => [
            'docs' => storage_path('api-docs'),
            'views' => base_path('resources/views/vendor/l5-swagger'),
            'excludes' => [],
        ],
        'scanOptions' => [
            'analyser' => null,
            'analysis' => null,
            'processors' => null,
            'pattern' => null,
            'exclude' => [],
        ],
        'generate_always' => false,
        'generate_yaml_copy' => false,
        'proxy' => false,
        'additional_config_url' => null,
        'operations_sort' => null,
        'validator_url' => null,
        'ui' => [
            'display' => [
                'doc_expansion' => 'none',
                'filter' => true,
            ],
            'authorization' => [
                'persist_authorization' => true,
                'oauth2_redirect_url' => null,
            ],
        ],
    ],
];
