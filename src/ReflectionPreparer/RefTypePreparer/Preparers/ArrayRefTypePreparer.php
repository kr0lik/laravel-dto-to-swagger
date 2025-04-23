<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\Preparers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\RefTypePreparerInterface;
use ReflectionNamedType;
use ReflectionType;
use Symfony\Component\PropertyInfo\Type;
use Traversable;

class ArrayRefTypePreparer implements RefTypePreparerInterface
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
        $propertyInfo = new Type(
            Type::BUILTIN_TYPE_ARRAY,
            $reflectionType->allowsNull(),
            null,
            true,
        );

        return [$propertyInfo];
    }

    public function supports(ReflectionType $reflectionType): bool
    {
        if (! $reflectionType instanceof ReflectionNamedType) {
            return false;
        }

        if ($reflectionType->getName() === Type::BUILTIN_TYPE_ARRAY) {
            return true;
        }

        if (is_subclass_of($reflectionType->getName(), Traversable::class)) {
            return true;
        }

        return false;
    }
}
