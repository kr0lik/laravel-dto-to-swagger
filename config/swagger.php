<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Symfony\Component\HttpFoundation\Response;

return [
    'default' => [
        'savePath' => base_path('swagger.yaml'),
        'includeMiddlewares' => ['api'], // string[]
        'includePatterns' => [], // string[]
        'excludeMiddlewares' => [], // string[]
        'excludePatterns' => [], // string[]
        'middlewaresToAuth' => ['auth:sanctum' => ['bearerAuth' => []]], // array<string, array<string, array<mixed>>>
        'tagFromControllerName' => false, // bool
        'tagFromControllerFolder' => false, // bool
        'tagFromActionFolder' => false, // bool
        'tagFromMiddlewares' => ['api', 'admin', 'web'], // string[]
        'fileUploadType' => SymfonyUploadedFile::class,
        'defaultErrorResponseSchemas' => [
            Response::HTTP_UNPROCESSABLE_ENTITY => [
                'description' => 'Unprocessable.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/ErrorResponse',
                        ],
                    ],
                ],
            ],
            Response::HTTP_INTERNAL_SERVER_ERROR => [
                'description' => 'Unexpected error.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/ErrorResponse',
                        ],
                    ],
                ],
            ],
        ], // array<int, array<string, mixed>>
        'requestErrorResponseSchemas' => [
            Response::HTTP_BAD_REQUEST => [
                'description' => 'Validation error',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/ValidationErrorResponse',
                        ],
                    ],
                ],
            ],
        ], // array<int, array<string, mixed>>

        'openApi' => [
            'info' => [
                'version' => '1.0.0',
                'title' => config('app.name'),
            ],
            'servers' => [['description' => 'dev', 'url' => config('app.url')]],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'api_token',
                    ],
                ],
                'schemas' => [
                    'JsonResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean'],
                            'message' => ['type' => 'string'],
                            'data' => ['nullable' => true],
                            'errors' => ['nullable' => true],
                        ],
                    ],
                    'ValidationErrorResponse' => [
                        'allOf' => [
                            ['$ref' => '#/components/schemas/JsonResponse'],
                            [
                                'properties' => [
                                    'success' => ['default' => false],
                                    'message' => ['default' => 'Validation Error.'],
                                    'data' => ['default' => null],
                                    'errors' => [
                                        'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']],
                                        'example' => [
                                            'field1' => ['is required'],
                                            'field2' => ['is required'],
                                        ],
                                        'nullable' => false,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'ErrorResponse' => [
                        'allOf' => [
                            ['$ref' => '#/components/schemas/JsonResponse'],
                            [
                                'properties' => [
                                    'success' => ['default' => false],
                                    'message' => ['default' => 'Unprocessable.'],
                                    'data' => ['default' => null],
                                    'errors' => [
                                        'type' => 'array', 'items' => ['type' => 'string'],
                                        'example' => ['Some error'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'else-one' => [
        'savePath' => base_path('swagger-else-one.yaml'),
        'includeMiddlewares' => [],
        'excludeMiddlewares' => ['api'],
        'openApi' => [
            'info' => [
                'version' => '1.0.0',
                'title' => 'Else One Swagger',
            ],
        ],
    ]
];
