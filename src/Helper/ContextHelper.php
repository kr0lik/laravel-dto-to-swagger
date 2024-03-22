<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\Helper;

use Kr0lik\DtoToSwagger\Attribute\Context;
use ReflectionAttribute;
use ReflectionProperty;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
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
                        $context = new Context(format: $context->format, pattern: self::phpToSwaggerDateTimeFormat($format));
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

                if ('' !== $format) {
                    $context = new Context(format: $context->format, pattern: self::phpToSwaggerDateTimeFormat($format));
                }
            }
        }
    }

    private static function phpToSwaggerDateTimeFormat(string $format): string
    {
        return str_replace(['Y', 'm', 'd', 'H', 'i', 's'], ['YYYY', 'MM', 'DD', 'HH', 'mm', 'ss'], $format);
    }
}
