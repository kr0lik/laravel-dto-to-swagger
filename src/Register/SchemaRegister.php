<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Register;

use OpenApi\Annotations\Schema;

final class SchemaRegister
{
    /** @var array<string, Schema> */
    private array $schemaRegister = [];

    /** @var array<string, string> */
    private array $nameRegister = [];

    public function register(Schema $schema, string $class): string
    {
        $path = $this->findPath($class);

        if (null !== $path) {
            return $path;
        }

        $shortName = $this->getShortName($class);

        $counter = array_count_values($this->nameRegister)[$shortName] ?? 0;

        if ($counter > 0) {
            $shortName .= '_'.$counter;
        }

        $this->nameRegister[$class] = $shortName;

        $this->schemaRegister[$shortName] = $schema;

        return $this->getPath($shortName);
    }

    public function findPath(string $class): ?string
    {
        if (array_key_exists($class, $this->nameRegister)) {
            return $this->getPath($this->getShortName($class));
        }

        return null;
    }

    /**
     * @return array<string, Schema>
     */
    public function getNamedSchemas(): array
    {
        return $this->schemaRegister;
    }

    private function getPath(string $shortName): string
    {
        return '#/components/schemas/'.$shortName;
    }

    private function getShortName(string $class): string
    {
        return basename(str_replace('\\', '/', $class), 'Dto');
    }
}
