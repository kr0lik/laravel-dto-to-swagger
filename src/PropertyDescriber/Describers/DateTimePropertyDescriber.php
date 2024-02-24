<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\PropertyDescriber\Describers;

use DateTimeInterface;
use Kr0lik\DtoToSwagger\PropertyDescriber\PropertyDescriberInterface;
use OpenApi\Annotations\Schema;
use Symfony\Component\PropertyInfo\Type;

class DateTimePropertyDescriber implements PropertyDescriberInterface
{
    public const CONTEXT_DATETIME_FORMAT = 'dateTimeFormat';
    public const CONTEXT_DATETIME_PATTERN = 'dateTimePattern';

    /**
     * @param array<string, mixed> $context
     */
    public function describe(Schema $property, array $context = [], Type ...$types): void
    {
        $property->type = 'string';
        $property->format = 'date-time';

        if (array_key_exists(self::CONTEXT_DATETIME_FORMAT, $context)) {
            $property->format = $context[self::CONTEXT_DATETIME_FORMAT];
        }

        if (array_key_exists(self::CONTEXT_DATETIME_PATTERN, $context)) {
            $property->pattern = $context[self::CONTEXT_DATETIME_PATTERN];
        }
    }

    public function supports(Type ...$types): bool
    {
        return 1 === count($types)
            && Type::BUILTIN_TYPE_OBJECT === $types[0]->getBuiltinType()
            && is_a($types[0]->getClassName(), DateTimeInterface::class, true);
    }
}
