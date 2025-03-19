# laravel-dto-to-swagger

## Overview

**laravel-dto-to-swagger** is an automatic Swagger documentation generator based on Laravel routing and strongly typed DTOs for request and response data.

💡 *No need to manually define schemas, tags, or routes!* The documentation is generated entirely based on your route definitions and DTOs. Just rely on your type annotations, and let the package handle the rest.

🚀 Integrates with [`spatie/laravel-data`](https://github.com/spatie/laravel-data), making Swagger generation seamless and fully automated.

📌 **Use `declare(strict_types=1);`** to ensure strict type safety. The schema fully reflects your type definitions without manual intervention.

🛠 **Based on `zircote/swagger-php`** for powerful OpenAPI support.

---

## Installation & Setup

```bash
composer require kr0lik/laravel-dto-to-swagger
```

Register the service provider in `config/app.php` (if needed):

```php
Kr0lik\DtoToSwagger\DtoToSwaggerServiceProvider::class,
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Kr0lik\DtoToSwagger\DtoToSwaggerServiceProvider"
```

---

## Configuration

The config file is located at `config/swagger.php`. You can define multiple configurations (e.g., `default`, `custom-config`).

**Key Configuration Options:**

- `savePath`: Path for the generated Swagger YAML file.
- `includeMiddlewares`, `excludeMiddlewares`: Define which middlewares to include/exclude.
- `includePatterns`, `excludePatterns`: Control route inclusion/exclusion patterns.
- `middlewaresToAuth`: Define authentication middlewares (e.g., `auth:sanctum`).
- `tagFromControllerName`, `tagFromActionFolder`, `tagFromMiddlewares`: Control Swagger tags generation.
- `fileUploadType`: Define the class used for file upload with `multipart/form-data` schema.
- `defaultErrorResponseSchemas`, `requestErrorResponseSchemas`: Configure default error response schemas.
- `openApi`: Base OpenAPI configuration, including:
    - `info`: API metadata (title, version, description, etc.).
    - `servers`: List of API servers.
    - `components`: Reusable elements like security schemes and schemas.
    - etc...
---

## Usage

Ensure that your controllers and DTOs are properly typed.

Update the config/swagger.php file according to your needs.

You can configure multiple configurations (e.g., default, else-one).

```php
//swagger.php
return [
    'default' => [
        'savePath' => base_path('swagger.yaml'),
        'includeMiddlewares' => ['api'], // string[]
        'includePatterns' => [], // string[]
        'excludeMiddlewares' => [], // string[]
        'excludePatterns' => [], // string[]
        'middlewaresToAuth' => ['auth:sanctum' => ['bearerAuth' => []]], // array<string, array<string, array<mixed>>>
        'tagFromControllerName' => true, // bool
        'tagFromActionFolder' => true, // bool
        'tagFromMiddlewares' => ['api', 'admin', 'web'], // string[]
        'fileUploadType' => SymfonyUploadedFile::class,
        'defaultErrorResponseSchemas' => [
            500 => [
                'description' => 'Error.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/ErrorResponse',
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
                ],
            ],
        ],
    ],
    'else-one' => [
        'savePath' => base_path('swagger-else-one.yaml'),
        'openApi' => [
            'info' => [
                'version' => '1.0.0',
                'title' => 'Else One Swagger',
            ],
        ],
        'servers' => [['description' => 'else-one', 'url' => config('app.url')]],
    ]
];
```

If the default config is specified, subsequent configs will be based on it. The default config is optional. The keys can be anything.

Ensure your controllers and actions are properly typed and utilize DTOs (Data Transfer Objects) for request and response data.

Run Swagger generation for default configuration:

```bash
php artisan swagger:generate
```
This generates a `swagger.yaml` file with fully automated API documentation.

If you want to generate using a specific configuration (for example, else-one), you can specify it like this:

```bash
php artisan swagger:generate else-one
```

This generates a `else-one-swagger.yaml` file with fully automated API documentation.

---

## Example

Automatically handle path parameters, query parameters, and request bodies with DTOs.

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

/**
 * This controller demonstrates how to define routes and generate Swagger documentation 
 * using annotations and DTOs in a Laravel application.
 */
class TextController extends Controller
{
    /**
     * Path in swagger will be generated automatic.
     *
     * @param RequestDto $requestDto The request will be generated automatic.
     * @param int|string|null $multipleVar Path parameter be added automatic.
     * @param array|null $arrayOptionalVar Optional be added automatic.
     *
     * @return ResponseDto The response will be generated automatic.
     */
    public function postAction(RequestDto $requestDto, int|string|null $multipleVar, ?array $arrayOptionalVar = []): ResponseDto
    {
        return new ResponseDto(1, 'string', new DateTimeImmutable());
    }
}
```

#### Request DTO Example

```php
<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Kr0lik\DtoToSwagger\Contract\JsonRequestInterface;

final class RequestDto implements JsonRequestInterface
{
    /**
     * @param array<int[]>                  $arrayWithSubArrayOfInt
     * @param SubDto[]                      $arrayOfDto
     * @param Collection<array-key, string> $collectionOfString
     */
    public function __construct(
        readonly string $string,
        readonly int $int,
        readonly float $float,
        readonly array $arrayWithSubArrayOfInt,
        readonly array $arrayOfDto,
        readonly SubDto $subDto,
        readonly object $objectNullable,
        readonly StringEnum $enum,
        readonly UploadedFile $uploadedFile,
        readonly Collection $collectionOfString,
    ) {
    }
}
```

#### Response DTO Example

```php
<?php

declare(strict_types=1);

namespace App\Dto\Response;

use Kr0lik\DtoToSwagger\Contract\JsonResponseInterface;

final class ResponseDto extends Data implements JsonResponseInterface
{
    public function __construct(
        /** Some Description */
        readonly int $int,
        readonly DateTimeImmutable $dateTime,
    ) {
    }
}
```

## Advanced Customization

Use OpenAPI attributes to refine your documentation:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use OpenApi\Attributes\Response;
use OpenApi\Attributes\Tag;

/**
 * This controller demonstrates how to define routes and generate Swagger documentation 
 * using annotations and DTOs in a Laravel application.
 */
class TextController extends Controller
{
    /**
     * @Tag("tagFromAttribute")
     * @Response(
     *     response=300, 
     *     description="response-from-attribute"
     * )
     */
    public function postAction(RequestDto $requestDto, int|string|null $multipleVar, ?array $arrayOptionalVar = []): ResponseDto
    {
        return new ResponseDto(1, 'string', new DateTimeImmutable());
    }
}
```
#### Request DTO Extend Example

```php
<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enum\StringEnum;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Kr0lik\DtoToSwagger\Attribute\Context;
use Kr0lik\DtoToSwagger\Attribute\Name;
use Kr0lik\DtoToSwagger\Contract\JsonRequestInterface;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\Property;
use stdClass;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class RequestDto implements JsonRequestInterface
{
    /**
     * @param string[]                      $arrayOfString
     * @param stdClass[]                    $arrayOfObject
     * @param array<int[]>                  $arrayWithSubArrayOfInt
     * @param SubDto[]                      $arrayOfDto
     * @param Collection<array-key, string> $collectionOfString
     */
    public function __construct(
        /** Some Description1 */
        #[Parameter(in: 'query')]
        readonly int $int,
        #[Property(description: 'some description', example: ['string', 'string2'])]
        readonly string $string,
        #[Parameter(name: 'X-FLOAT', in: 'header')]
        readonly float $float,
        #[Name('datetime')]
        #[Context(pattern: 'Y-m-d H:i:s')]
        readonly DateTimeImmutable $dateTimeImmutable,
        readonly array $arrayOfString,
        /** Some Description2 */
        readonly array $arrayOfObject,
        readonly array $arrayWithSubArrayOfInt,
        readonly SubDto $subDto,
        readonly array $arrayOfDto,
        readonly object $objectNullable,
        readonly StringEnum $enum,
        readonly UploadedFile $uploadedFile,
        readonly Collection $collectionOfString,
    ) {
    }
}
```
#### Query Request DTO Extend Example

```php
<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Kr0lik\DtoToSwagger\Attribute\Nested;
use Kr0lik\DtoToSwagger\Contract\QueryRequestInterface;
use OpenApi\Attributes\Parameter;

class QueryRequest implements QueryRequestInterface
{
    public function __construct(
        readonly int $page,
        #[Parameter(name: 'per-page')]
        readonly int $perPage = 20,

        #[Nested]
        readonly SortDto $sort,
    ) {
    }
}
```
#### Header Request DTO Extend Example

```php
<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Kr0lik\DtoToSwagger\Contract\HeaderRequestInterface;
use OpenApi\Attributes\Parameter;

final class HeadRequestDto implements HeaderRequestInterface
{
    public function __construct(
        #[Parameter(name: 'X-DEVICE-ID')]
        readonly string $deviceId,
    ) {
    }
}
```
See example folder

## Develop
docker pull composer:2.2.20

docker run -v .:/app --rm composer:2.2.20 composer install

docker run -v .:/app --rm composer:2.2.20 bin/php-cs-fixer fix

docker run -v .:/app --rm composer:2.2.20 bin/phpstan analyse


## See Also
📌 **[zircote/swagger-php](https://github.com/zircote/swagger-php)** – Base library used.

📌 **[spatie/laravel-data](https://github.com/spatie/laravel-data)** – Optional integration for DTO management.




