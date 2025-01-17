<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Trait;

use ReflectionProperty;

trait IsRequiredTrait
{
    private function isRequired(ReflectionProperty $reflectionProperty): bool
    {
        if ($reflectionProperty->hasDefaultValue()) {
            return false;
        }

        foreach ($reflectionProperty->getDeclaringClass()->getConstructor()?->getParameters() ?? [] as $constructorParameter) {
            if ($constructorParameter->getName() === $reflectionProperty->getName() && $constructorParameter->isDefaultValueAvailable()) {
                return false;
            }
        }

        return true;
    }
}
