<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers;

use DateTimeInterface;
use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriberInterface;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;
use Symfony\Component\PropertyInfo\Type;

class DateTimeDescriber implements PropertyTypeDescriberInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @throws InvalidArgumentException
     */
    public function describe(Schema $property, array $context = [], Type ...$types): void
    {
        $property->type = 'string';

        if ($property->format === null || $property->format === Generator::UNDEFINED) {
            $property->format = 'date-time';
        }

        Util::merge($property, $context, true);
    }

    public function supports(Type ...$types): bool
    {
        return count($types) === 1
            && $types[0]->getBuiltinType() === Type::BUILTIN_TYPE_OBJECT
            && $types[0]->getClassName() !== null
            && is_a($types[0]->getClassName(), DateTimeInterface::class, true);
    }
}
