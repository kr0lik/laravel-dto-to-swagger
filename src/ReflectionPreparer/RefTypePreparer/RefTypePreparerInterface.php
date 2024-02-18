<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer;

use ReflectionType;
use Symfony\Component\PropertyInfo\Type;

interface RefTypePreparerInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @return Type[]
     */
    public function prepare(ReflectionType $reflectionType, array $context = []): array;

    public function supports(ReflectionType $reflectionType): bool;
}
