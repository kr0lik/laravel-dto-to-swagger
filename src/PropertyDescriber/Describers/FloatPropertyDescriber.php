<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger\PropertyDescriber\Describers;

use InvalidArgumentException;
use Kr0lik\DtoToSwagger\Helper\Util;
use Kr0lik\DtoToSwagger\PropertyDescriber\PropertyDescriberInterface;
use OpenApi\Annotations\Schema;
use Symfony\Component\PropertyInfo\Type;

class FloatPropertyDescriber implements PropertyDescriberInterface
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
        return 1 === count($types) && Type::BUILTIN_TYPE_FLOAT === $types[0]->getBuiltinType();
    }
}
