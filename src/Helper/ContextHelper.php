<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Helper;

use Kr0lik\DtoToSwagger\Attribute\Context;
use ReflectionAttribute;
use ReflectionProperty;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;

class ContextHelper
{
    /**
     * @return array<string, mixed>
     */
    public static function getContext(ReflectionProperty $reflectionProperty): array
    {
        $context = new Context();

        foreach ($reflectionProperty->getAttributes() as $attribute) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof Context) {
                $context = $attributeInstance;
            }
        }

        foreach ($reflectionProperty->getAttributes() as $attribute) {
            self::fillFromLaravelData($attribute, $context);
        }

        return $context->jsonSerialize();
    }

    private static function fillFromLaravelData(ReflectionAttribute $attribute, Context &$context): void
    {
        self::fillFromDateTimeValidation($attribute, $context);
        self::fillFromDateTimeTransformer($attribute, $context);
        self::fillInValidation($attribute, $context);
    }

    private static function fillFromDateTimeTransformer(ReflectionAttribute $attribute, Context &$context): void
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

                    if ('' !== $format) {
                        $context = new Context(format: $context->format, pattern: self::phpToSwaggerDateTimePattern($format));
                    }
                }
            }
        }
    }

    private static function fillFromDateTimeValidation(ReflectionAttribute $attribute, Context &$context): void
    {
        if (class_exists(DateFormat::class)) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof DateFormat) {
                $format = $attributeInstance->parameters()[0] ?? '';

                if (is_array($format)) {
                    $format = current($format);
                }

                if (is_string($format) && '' !== $format) {
                    $context = new Context(format: $context->format, pattern: self::phpToSwaggerDateTimePattern($format));
                }
            }
        }
    }

    private static function phpToSwaggerDateTimePattern(string $format): string
    {
        $format = str_replace('\\', '\\\\', $format);

        $replacements = [
            'Y' => '[0-9]{4}',  // Year
            'm' => '[0-9]{2}',  // Month
            'd' => '[0-9]{2}',  // Day
            'H' => '[0-9]{2}',  // Hour
            'i' => '[0-9]{2}',  // Minute
            's' => '[0-9]{2}',  // Second
            'P' => '[+-][0-9]{2}:[0-9]{2}', // Timezone
        ];

        $pattern = str_replace(array_keys($replacements), array_values($replacements), $format);

        return '^'.$pattern.'$';
    }

    private static function fillInValidation(ReflectionAttribute $attribute, Context &$context): void
    {
        if (class_exists(In::class)) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof In) {
                // in:"wallet","-wallet","amount","-amount","status","-status","createdAt","-createdAt"
                $format = (string) $attributeInstance;

                $values = explode(',', str_replace(['in:', '"'], '', $format));

                $context = new Context(enum: $values);
            }
        }
    }
}
