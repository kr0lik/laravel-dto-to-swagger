<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\Preparers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\RefTypePreparerInterface;
use ReflectionNamedType;
use ReflectionType;
use Symfony\Component\PropertyInfo\Type;

class StringRefTypePreparer implements RefTypePreparerInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @throws InvalidArgumentException
     *
     * @return Type[]
     */
    public function prepare(ReflectionType $reflectionType, array $context = []): array
    {
        $propertyInfo = new Type(Type::BUILTIN_TYPE_STRING, $reflectionType->allowsNull());

        return [$propertyInfo];
    }

    public function supports(ReflectionType $reflectionType): bool
    {
        if (! $reflectionType instanceof ReflectionNamedType) {
            return false;
        }

        return $reflectionType->getName() === Type::BUILTIN_TYPE_STRING;
    }
}
