<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Helper;

use Kr0lik\DtoToSwagger\Attribute\Name;
use Kr0lik\DtoToSwagger\Contract\JsonResponseInterface;
use Kr0lik\DtoToSwagger\ReflectionPreparer\Helper\ClassHelper;
use ReflectionAttribute;
use ReflectionProperty;
use Spatie\LaravelData\Attributes\MapInputName;
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

            $name = self::fromLaravelData($attribute, $reflectionProperty);

            if (null !== $name && '' !== $name) {
                return $name;
            }
        }

        return $reflectionProperty->getName();
    }

    private static function fromLaravelData(ReflectionAttribute $attribute, ReflectionProperty $reflectionProperty): ?string
    {
        if (class_exists(MapInputName::class) && class_exists(MapOutputName::class)) {
            $attributeInstance = $attribute->newInstance();

            $isResponse = ClassHelper::isImplementsRecursively($reflectionProperty->getDeclaringClass(), JsonResponseInterface::class);

            if (!$isResponse && $attributeInstance instanceof MapInputName) {
                return (string) $attributeInstance->input;
            }

            if ($isResponse && $attributeInstance instanceof MapOutputName) {
                return (string) $attributeInstance->output;
            }
        }

        return null;
    }
}
