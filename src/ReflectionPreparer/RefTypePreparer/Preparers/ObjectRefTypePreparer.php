<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\Preparers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\RefTypePreparerInterface;
use ReflectionNamedType;
use ReflectionType;
use stdClass;
use Symfony\Component\PropertyInfo\Type;
use Traversable;

class ObjectRefTypePreparer implements RefTypePreparerInterface
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
        assert($reflectionType instanceof ReflectionNamedType);

        $className = $reflectionType->getName();

        if ($className === Type::BUILTIN_TYPE_OBJECT || $className === stdClass::class) {
            $className = null;
        }

        $propertyInfo = new Type(Type::BUILTIN_TYPE_OBJECT, $reflectionType->allowsNull(), $className);

        return [$propertyInfo];
    }

    public function supports(ReflectionType $reflectionType): bool
    {
        if (! $reflectionType instanceof ReflectionNamedType) {
            return false;
        }

        if ($reflectionType->getName() === Type::BUILTIN_TYPE_OBJECT) {
            return true;
        }

        if (is_subclass_of($reflectionType->getName(), Traversable::class)) {
            return false;
        }

        return class_exists($reflectionType->getName());
    }
}
