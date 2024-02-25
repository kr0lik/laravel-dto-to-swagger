<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Helper;

use Kr0lik\DtoToSwagger\Attribute\Context;
use ReflectionProperty;

class ContextHelper
{
    /**
     * @return array<string, mixed>
     */
    public static function getContext(ReflectionProperty $reflectionProperty): array
    {
        foreach ($reflectionProperty->getAttributes() as $attribute) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof Context) {
                return $attributeInstance->jsonSerialize();
            }
        }

        return [];
    }
}
