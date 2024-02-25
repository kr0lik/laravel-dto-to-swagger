<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\ReflectionPreparer\Helper;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;

class ClassHelper
{
    /**
     * @return iterable<ReflectionAttribute>
     */
    public static function getAllAttributes(ReflectionClass $reflectionClass): iterable
    {
        foreach ($reflectionClass->getAttributes() as $reflectionAttribute) {
            yield $reflectionAttribute;
        }

        $reflectionParentClass = $reflectionClass->getParentClass();

        if (false === $reflectionParentClass) {
            return;
        }

        foreach ($reflectionParentClass->getAttributes() as $attribute) {
            yield $attribute;
        }
    }

    /**
     * @return iterable<ReflectionProperty>
     */
    public static function getVisibleProperties(ReflectionClass $reflectionClass): iterable
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

        foreach ($reflectionParentClass->getProperties() as $reflectionProperty) {
            if (!self::isVisible($reflectionClass, $reflectionProperty)) {
                continue;
            }

            yield $reflectionProperty;
        }
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
