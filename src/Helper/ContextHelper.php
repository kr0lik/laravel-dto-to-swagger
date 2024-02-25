<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Helper;

use Kr0lik\DtoToSwagger\Attribute\Context;
use ReflectionAttribute;
use ReflectionProperty;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;

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

            $context = self::fromLaravelData($attribute);

            if (null !== $context) {
                return $context->jsonSerialize();
            }
        }

        return [];
    }

    private static function fromLaravelData(ReflectionAttribute $attribute): ?Context
    {
        if (class_exists(WithTransformer::class)) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof WithTransformer) {
                $transformer = $attributeInstance->get();

                if (
                    class_exists(DateTimeInterfaceTransformer::class)
                    && $transformer instanceof DateTimeInterfaceTransformer
                ) {
                    $format = $attributeInstance->arguments['format'] ?? '';

                    if ('' === $format) {
                        return null;
                    }

                    return new Context(pattern: $format);
                }
            }
        }

        return null;
    }
}
