<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Register;

use Kr0lik\DtoToSwagger\Dto\ConfigDto;
use Kr0lik\DtoToSwagger\Helper\Util;
use OpenApi\Annotations\Components;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\PathItem;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;
use RuntimeException;

final class OpenApiRegister
{
    /** @var array<string, string> */
    private array $nameRegister = [];

    private ?ConfigDto $config = null;

    private ?OpenApi $openApi = null;

    public function initOpenApi(ConfigDto $config): void
    {
        $this->config = $config;

        $this->openApi = new OpenApi($this->config->openApi);

        $this->initPaths();
        $this->initComponents();
        $this->initSchemas();
    }

    public function getOpenApi(): ?OpenApi
    {
        return $this->openApi;
    }

    public function getConfig(): ?ConfigDto
    {
        return $this->config;
    }

    public function registerSchema(Schema $schema, string $class): string
    {
        if (null === $this->openApi) {
            throw new RuntimeException('OpenApi does not inited');
        }

        $path = $this->findSchemaPath($class);

        if (null !== $path) {
            return $path;
        }

        $shortName = $this->getShortName($class);

        $counter = array_count_values($this->nameRegister)[$shortName] ?? 0;

        if (Generator::UNDEFINED === $this->openApi->components) {
            $this->openApi->components = new Components([]);
        }

        if (Generator::UNDEFINED === $this->openApi->components->schemas) {
            $this->openApi->components->schemas = [];
        }

        foreach ($this->openApi->components->schemas as $existsSchema) {
            if ($existsSchema->schema === $shortName) {
                ++$counter;

                break;
            }
        }

        if ($counter > 0) {
            $shortName .= '_'.$counter;
        }

        $this->nameRegister[$class] = $shortName;

        $schema->schema = $shortName;

        $this->openApi->components->schemas[] = $schema;

        return $this->getPath($shortName);
    }

    public function findSchemaPath(string $class): ?string
    {
        if (array_key_exists($class, $this->nameRegister)) {
            return $this->getPath($this->getShortName($class));
        }

        return null;
    }

    private function initPaths(): void
    {
        $openApi = $this->openApi;

        assert($openApi instanceof OpenApi);

        if (Generator::UNDEFINED === $openApi->paths) {
            return;
        }

        /** @var array<string, PathItem|array<string, mixed>> $paths */
        $paths = $openApi->paths;

        $openApi->paths = [];

        foreach ($paths as $route => $pathData) {
            if ($pathData instanceof PathItem) {
                continue;
            }

            $pathItem = Util::getPath($openApi, $route);
            Util::merge($pathItem, $pathData);
        }
    }

    private function initComponents(): void
    {
        $openApi = $this->openApi;

        assert($openApi instanceof OpenApi);

        if (Generator::UNDEFINED === $openApi->components) {
            return;
        }

        /** @var Components|array<string, mixed> $components */
        $components = $openApi->components;

        if (is_array($components)) {
            $openApi->components = new Components($components);
        }
    }

    private function initSchemas(): void
    {
        $openApi = $this->openApi;

        assert($openApi instanceof OpenApi);

        /** @var Components|string $components */
        $components = $openApi->components;

        if (!$components instanceof Components) {
            return;
        }

        if (Generator::UNDEFINED === $components->schemas) {
            $components->schemas = [];
        }

        /** @var array<string|int, mixed> $schemas */
        $schemas = $components->schemas;

        foreach ($schemas as $key => $existsSchema) {
            if ($existsSchema instanceof Schema) {
                continue;
            }

            $schema = new Schema($existsSchema);

            if (Generator::UNDEFINED === $schema->schema && is_string($key)) {
                $schema->schema = $key;
            }

            $components->schemas[$key] = $schema;
        }
    }

    private function getPath(string $shortName): string
    {
        return '#/components/schemas/'.$shortName;
    }

    private function getShortName(string $class): string
    {
        return basename(str_replace('\\', '/', $class));
    }
}
