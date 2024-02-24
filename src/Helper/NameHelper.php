<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Helper;

use Kr0lik\DtoToSwagger\Attribute\Name;
use ReflectionProperty;

class NameHelper
{
    public static function getName(ReflectionProperty $reflectionProperty): string
    {
        foreach ($reflectionProperty->getAttributes() as $attribute) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof Name) {
                return $attributeInstance->name;
            }
        }

        return $reflectionProperty->getName();
    }
}
