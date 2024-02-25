<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\ReflectionPreparer\Helper;

use ReflectionAttribute;
use ReflectionClass;

class GetHelper
{
    /**
     * @return iterable<ReflectionAttribute>
     */
    public static function getAllAttributes(ReflectionClass $reflectionClass): iterable
    {
        foreach ($reflectionClass->getAttributes() as $attribute) {
            yield $attribute;
        }

        $parentClass = $reflectionClass->getParentClass();

        if (false === $parentClass) {
            return;
        }

        foreach ($parentClass->getAttributes() as $attribute) {
            yield $attribute;
        }
    }
}
