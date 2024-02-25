<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Helper;

use Kr0lik\DtoToSwagger\Attribute\Name;
use ReflectionAttribute;
use ReflectionProperty;
use Spatie\LaravelData\Attributes\MapOutputName;

class NameHelper
{
    public static function getName(ReflectionProperty $reflectionProperty): string
    {
        foreach ($reflectionProperty->getAttributes() as $attribute) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof Name) {
                return $attributeInstance->name;
            }

            $name = self::fromLaravelData($attribute);

            if (null !== $name && '' !== $name) {
                return $name;
            }
        }

        return $reflectionProperty->getName();
    }

    private static function fromLaravelData(ReflectionAttribute $attribute): ?string
    {
        if (class_exists(MapOutputName::class)) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof MapOutputName) {
                return (string) $attributeInstance->output;
            }
        }

        return null;
    }
}
