<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Register;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Dto\ConfigDto;
use Kr0lik\DtoToSwagger\Helper\Util;
use OpenApi\Annotations\Components;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\PathItem;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;

final class OpenApiRegister
{
    /** @var array<string, string> */
    private array $nameRegister = [];

    private ConfigDto $config;
    private OpenApi $openApi;

    /**
     * @throws InvalidArgumentException
     */
    public function initOpenApi(ConfigDto $config): void
    {
        $this->config = $config;

        $this->openApi = new OpenApi($this->config->openApi);

        $paths = $this->openApi->paths;

        if (Generator::UNDEFINED !== $paths) {
            /** @phpstan-ignore-next-line  */
            $this->openApi->paths = Generator::UNDEFINED;

            foreach ($paths as $route => $pathData) {
                if ($pathData instanceof PathItem) {
                    continue;
                }

                $pathItem = Util::getPath($this->openApi, $route);
                Util::merge($pathItem, $pathData);
            }
        }
    }

    public function getOpenApi(): OpenApi
    {
        return $this->openApi;
    }

    public function getConfig(): ConfigDto
    {
        return $this->config;
    }

    public function registerSchema(Schema $schema, string $class): string
    {
        $path = $this->findSchemaPath($class);

        if (null !== $path) {
            return $path;
        }

        $shortName = $this->getShortName($class);

        $counter = array_count_values($this->nameRegister)[$shortName] ?? 0;

        if (Generator::UNDEFINED === $this->openApi->components) {
            $this->openApi->components = new Components([]);
        }

        if (is_array($this->openApi->components)) {
            $this->openApi->components = new Components($this->openApi->components);
        }

        if (Generator::UNDEFINED === $this->openApi->components->schemas) {
            $this->openApi->components->schemas = [];
        }

        foreach ($this->openApi->components->schemas as $name => $existsSchema) {
            if ($name === $shortName || ($existsSchema instanceof Schema && $existsSchema->schema === $shortName)) {
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

    private function getPath(string $shortName): string
    {
        return '#/components/schemas/'.$shortName;
    }

    private function getShortName(string $class): string
    {
        return basename(str_replace('\\', '/', $class));
    }
}
