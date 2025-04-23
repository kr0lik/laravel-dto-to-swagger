<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriberInterface;
use OpenApi\Annotations\Schema;
use Symfony\Component\PropertyInfo\Type;

class FloatDescriber implements PropertyTypeDescriberInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @throws InvalidArgumentException
     */
    public function describe(Schema $property, array $context = [], Type ...$types): void
    {
        $property->type = 'number';
        $property->format = 'float';

        Util::merge($property, $context, true);
    }

    public function supports(Type ...$types): bool
    {
        return count($types) === 1 && $types[0]->getBuiltinType() === Type::BUILTIN_TYPE_FLOAT;
    }
}
