<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Helper;

use Kr0lik\DtoToSwagger\Attribute\Context;
use ReflectionMethod;
use ReflectionProperty;

class ContextHelper
{
    /**
     * @return array<string, mixed>
     */
    public static function getContext(ReflectionMethod|ReflectionProperty $reflectionProperty): array
    {
        $context = [];

        foreach ($reflectionProperty->getAttributes() as $attribute) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof Context) {
                $context = array_merge($context, $attributeInstance->context);
            }
        }

        return $context;
    }
}
