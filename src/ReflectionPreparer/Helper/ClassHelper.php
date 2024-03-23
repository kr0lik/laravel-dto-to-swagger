<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\ReflectionPreparer\Helper;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;

class ClassHelper
{
    public static function isImplementsRecursively(ReflectionClass $reflectionClass, string $interface): bool
    {
        if ($reflectionClass->implementsInterface($interface)) {
            return true;
        }

        $reflectionParentClass = $reflectionClass->getParentClass();

        if (false === $reflectionParentClass) {
            return false;
        }

        return self::isImplementsRecursively($reflectionParentClass, $interface);
    }

    /**
     * @return iterable<ReflectionAttribute>
     */
    public static function getAttributesRecursively(ReflectionClass $reflectionClass): iterable
    {
        foreach ($reflectionClass->getAttributes() as $reflectionAttribute) {
            yield $reflectionAttribute;
        }

        $reflectionParentClass = $reflectionClass->getParentClass();

        if (false === $reflectionParentClass) {
            return;
        }

        yield from self::getAttributesRecursively($reflectionParentClass);
    }

    /**
     * @return iterable<ReflectionProperty>
     */
    public static function getVisiblePropertiesRecursively(ReflectionClass $reflectionClass): iterable
    {
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if (!self::isVisible($reflectionClass, $reflectionProperty)) {
                continue;
            }

            yield $reflectionProperty;
        }

        $reflectionParentClass = $reflectionClass->getParentClass();

        if (false === $reflectionParentClass) {
            return;
        }

        yield from self::getVisiblePropertiesRecursively($reflectionParentClass);
    }

    private static function isVisible(ReflectionClass $reflectionClass, ReflectionProperty $reflectionProperty): bool
    {
        if ($reflectionProperty->isStatic()) {
            return false;
        }

        if ($reflectionProperty->isPrivate()) {
            return false;
        }

        if ($reflectionProperty->isProtected()) {
            return false;
        }

        if ($reflectionClass->getName() !== $reflectionProperty->getDeclaringClass()->getName()) {
            return false;
        }

        return true;
    }
}
