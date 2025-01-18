<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Helper;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class AttributeHelper
{
    /**
     * @throws ReflectionException
     *
     * @return iterable<ReflectionAttribute>
     */
    public static function getAttributes(ReflectionMethod $reflectionMethod): iterable
    {
        $reflectionClass = new ReflectionClass($reflectionMethod->class);

        foreach ($reflectionClass->getAttributes() as $attribute) {
            yield $attribute;
        }

        foreach ($reflectionMethod->getAttributes() as $attribute) {
            yield $attribute;
        }
    }
}
