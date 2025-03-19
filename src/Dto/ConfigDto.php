<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Dto;

readonly class ConfigDto
{
    /**
     * @param array<string, mixed>                       $openApi
     * @param string[]                                   $includeMiddlewares
     * @param string[]                                   $includePatterns
     * @param string[]                                   $excludeMiddlewares
     * @param string[]                                   $excludePatterns
     * @param array<string, array<string, array<mixed>>> $middlewaresToAuth
     * @param string[]                                   $tagFromMiddlewares
     * @param array<int, array<string, mixed>>           $defaultErrorResponseSchemas
     * @param array<int, array<string, mixed>>           $requestErrorResponseSchemas
     */
    public function __construct(
        public string $savePath,
        public array $openApi,
        public array $includeMiddlewares,
        public array $includePatterns,
        public array $excludeMiddlewares,
        public array $excludePatterns,
        public array $middlewaresToAuth,
        public bool $tagFromControllerName,
        public bool $tagFromControllerFolder,
        public bool $tagFromActionFolder,
        public array $tagFromMiddlewares,
        public string $fileUploadType,
        public array $defaultErrorResponseSchemas,
        public array $requestErrorResponseSchemas,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            savePath: $data['savePath'],
            openApi: $data['openApi'],
            includeMiddlewares: $data['includeMiddlewares'] ?? [],
            includePatterns: $data['includePatterns'] ?? [],
            excludeMiddlewares: $data['excludeMiddlewares'] ?? [],
            excludePatterns: $data['excludePatterns'] ?? [],
            middlewaresToAuth: $data['middlewaresToAuth'] ?? [],
            tagFromControllerName: $data['tagFromControllerName'] ?? false,
            tagFromControllerFolder: $data['tagFromControllerFolder'] ?? false,
            tagFromActionFolder: $data['tagFromActionFolder'] ?? false,
            tagFromMiddlewares: $data['tagFromMiddlewares'] ?? [],
            fileUploadType: $data['fileUploadType'] ?? '',
            defaultErrorResponseSchemas: $data['defaultErrorResponseSchemas'] ?? [],
            requestErrorResponseSchemas: $data['requestErrorResponseSchemas'] ?? [],
        );
    }
}
