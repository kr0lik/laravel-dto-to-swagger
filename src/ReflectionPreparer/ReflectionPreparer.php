<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\ReflectionPreparer;

use Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\RefTypePreparer;
use ReflectionMethod;
use ReflectionProperty;
use ReflectionType;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type;

class ReflectionPreparer
{
    public function __construct(
        private PropertyInfoExtractor $propertyInfoExtractor,
        private PhpDocReader $phpDocReader,
        private RefTypePreparer $reflectionTypePreparer,
    ) {}

    /**
     * @return Type[]
     */
    public function getTypes(ReflectionProperty $reflectionProperty): array
    {
        return $this->propertyInfoExtractor->getTypes(
            $reflectionProperty->getDeclaringClass()->getName(),
            $reflectionProperty->getName(),
        ) ?? [];
    }

    /**
     * @return iterable<string, Type[]>
     */
    public function getArgumentTypes(ReflectionMethod $reflectionMethod): iterable
    {
        $phpDocNamedParameters = $this->phpDocReader->getNamedParamTypes($reflectionMethod);

        foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
            if (array_key_exists($reflectionParameter->getName(), $phpDocNamedParameters)) {
                yield $reflectionParameter->getName() => $phpDocNamedParameters[$reflectionParameter->getName()];

                continue;
            }

            if (! $reflectionParameter->getType() instanceof ReflectionType) {
                continue;
            }

            yield $reflectionParameter->getName() => $this->reflectionTypePreparer->prepare($reflectionParameter->getType());
        }
    }

    /**
     * @return Type[]|null
     */
    public function getReturnTypes(ReflectionMethod $reflectionMethod): ?array
    {
        $phpDocReturnType = $this->phpDocReader->getReturnTypes($reflectionMethod);

        if ($phpDocReturnType !== null) {
            return $phpDocReturnType;
        }

        $reflectionReturnType = $reflectionMethod->getReturnType();

        if ($reflectionReturnType instanceof ReflectionType) {
            return $this->reflectionTypePreparer->prepare($reflectionReturnType);
        }

        return null;
    }
}
