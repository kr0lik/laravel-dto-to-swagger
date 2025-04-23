<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\Preparers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\RefTypePreparer;
use Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\RefTypePreparerInterface;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use Symfony\Component\PropertyInfo\Type;

class CompoundRefTypePreparer implements RefTypePreparerInterface
{
    public function __construct(
        private RefTypePreparer $refTypePreparer,
    ) {}

    /**
     * @param array<string, mixed> $context
     *
     * @throws InvalidArgumentException
     *
     * @return Type[]
     */
    public function prepare(ReflectionType $reflectionType, array $context = []): array
    {
        assert($reflectionType instanceof ReflectionUnionType);

        $propertyInfos = [];

        $hasNullable = false;

        /** @var ReflectionNamedType $type */
        foreach ($reflectionType->getTypes() as $type) {
            if ($type->getName() === Type::BUILTIN_TYPE_NULL) {
                $hasNullable = true;

                continue;
            }

            $propertyInfos = array_merge($propertyInfos, $this->refTypePreparer->prepare($type, $context));
        }

        if ($hasNullable) {
            $propertyInfos = array_map(
                static fn (Type $type): Type => new Type($type->getBuiltinType(), true, $type->getClassName(), $type->isCollection(), $type->getCollectionKeyTypes(), $type->getCollectionValueTypes()),
                $propertyInfos
            );
        }

        return $propertyInfos;
    }

    public function supports(ReflectionType $reflectionType): bool
    {
        if ($reflectionType instanceof ReflectionUnionType) {
            return true;
        }

        return false;
    }
}
