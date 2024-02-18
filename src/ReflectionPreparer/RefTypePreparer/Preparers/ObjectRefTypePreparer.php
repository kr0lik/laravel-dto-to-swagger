<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\Preparers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\RefTypePreparerInterface;
use ReflectionNamedType;
use ReflectionType;
use Symfony\Component\PropertyInfo\Type;
use Traversable;

class ObjectRefTypePreparer implements RefTypePreparerInterface
{
    public const SOURCE_CLASS_CONTEXT = 'sourceClass';

    /**
     * @param array<string, mixed> $context
     *
     * @throws InvalidArgumentException
     *
     * @return Type[]
     */
    public function prepare(ReflectionType $reflectionType, array $context = []): array
    {
        assert($reflectionType instanceof ReflectionNamedType);

        $propertyInfo = new Type(Type::BUILTIN_TYPE_OBJECT, $reflectionType->allowsNull(), $reflectionType->getName());

        return [$propertyInfo];
    }

    public function supports(ReflectionType $reflectionType): bool
    {
        if (!$reflectionType instanceof ReflectionNamedType) {
            return false;
        }

        if (Type::BUILTIN_TYPE_OBJECT === $reflectionType->getName()) {
            return true;
        }

        if (is_subclass_of($reflectionType->getName(), Traversable::class)) {
            return false;
        }

        return class_exists($reflectionType->getName());
    }
}
